/* =========================
   EMPFOHLENE DB-ANPASSUNGEN
   (optional, aber stark empfohlen)
   ========================= */

/* 1) Timestamps: statt '0000-00-00' besser CURRENT_TIMESTAMP (MySQL strict mode) */
ALTER TABLE fv_benutzer
  MODIFY be_erstell TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY be_new_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  MODIFY be_changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE fv_erlauben
  MODIFY fe_pw_chgd_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  MODIFY fe_erstdat    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY fe_changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE fv_rolle
  MODIFY fr_new_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY fr_changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE fv_rollen_beschr
  MODIFY fl_new_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY fl_changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE fv_mand_erl
  MODIFY fu_new_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY fu_changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

/* 2) Eindeutigkeit: User-ID eindeutig */
ALTER TABLE fv_benutzer
  ADD UNIQUE KEY uq_fv_benutzer_uid (be_uid);

/* 3) Pro Benutzer+Mandant nur ein Recht (für Upsert/Update) */
ALTER TABLE fv_mand_erl
  ADD UNIQUE KEY uq_fv_mand_erl_be_ei (be_id, ei_id);

/* 4) Rollen: pro Benutzer+Rolle nur einmal */
ALTER TABLE fv_rolle
  ADD UNIQUE KEY uq_fv_rolle_be_fl (be_id, fl_id);

/* 5) Passwort-Tabelle: Index auf be_id */
ALTER TABLE fv_erlauben
  ADD INDEX ix_fv_erlauben_be (be_id);
/* =========================
   CRUD-SQL (Beispiele)
   ========================= */

/* ---- Benutzer (fv_benutzer) ---- */

/* Create */
INSERT INTO fv_benutzer (be_uid, be_erst_von, be_new_id, be_changed_uid)
VALUES (:be_uid, :be_erst_von, :be_new_id, :be_changed_uid);

/* Read */
SELECT * FROM fv_benutzer WHERE be_id = :be_id;
SELECT * FROM fv_benutzer WHERE be_uid = :be_uid;

/* Update */
UPDATE fv_benutzer
SET be_changed_uid = :be_changed_uid
WHERE be_id = :be_id;

/* Delete */
DELETE FROM fv_benutzer WHERE be_id = :be_id;


/* ---- Passwort (fv_erlauben) ---- */

/* Create (neuer Passwort-Hash, historisierend) */
INSERT INTO fv_erlauben (be_id, fe_pw, fe_pw_chgd_id, fe_new_id, fe_chandged_id)
VALUES (:be_id, :fe_pw, :fe_pw_chgd_id, :fe_new_id, :fe_chandged_id);

/* Read (letztes Passwort) */
SELECT fe_pw
FROM fv_erlauben
WHERE be_id = :be_id
ORDER BY fe_id DESC
LIMIT 1;

/* Optional: Delete Passwort-Historie */
DELETE FROM fv_erlauben WHERE be_id = :be_id;


/* ---- Rollenbeschreibung (fv_rollen_beschr) ---- */

/* Create */
INSERT INTO fv_rollen_beschr (fl_Beschreibung, fl_module, fl_eigner, fl_new_id, fl_changed_id)
VALUES (:fl_Beschreibung, :fl_module, :fl_eigner, :fl_new_id, :fl_changed_id);

/* Read */
SELECT * FROM fv_rollen_beschr WHERE fl_id = :fl_id;

/* Update */
UPDATE fv_rollen_beschr
SET fl_Beschreibung = :fl_Beschreibung,
    fl_module = :fl_module,
    fl_eigner = :fl_eigner,
    fl_changed_id = :fl_changed_id
WHERE fl_id = :fl_id;

/* Delete */
DELETE FROM fv_rollen_beschr WHERE fl_id = :fl_id;


/* ---- Rollen-Zuordnung (fv_rolle) ---- */

/* Create */
INSERT INTO fv_rolle (be_id, fl_id, fr_new_uid, fr_changed_uid)
VALUES (:be_id, :fl_id, :fr_new_uid, :fr_changed_uid);

/* Read (User Rollen inkl. Beschr.) */
SELECT r.*, b.fl_Beschreibung, b.fl_module, b.fl_eigner
FROM fv_rolle r
JOIN fv_rollen_beschr b ON b.fl_id = r.fl_id
WHERE r.be_id = :be_id;

/* Update (selten; meist delete+insert) */
UPDATE fv_rolle
SET fr_changed_uid = :fr_changed_uid
WHERE fr_id = :fr_id;

/* Delete */
DELETE FROM fv_rolle WHERE fr_id = :fr_id;


/* ---- Mandantenrechte (fv_mand_erl) ---- */

/* Upsert (wenn UNIQUE(be_id, ei_id) gesetzt ist) */
INSERT INTO fv_mand_erl (be_id, ei_id, fu_erlauben, fu_new_uid, fu_changed_uid)
VALUES (:be_id, :ei_id, :fu_erlauben, :fu_new_uid, :fu_changed_uid)
ON DUPLICATE KEY UPDATE
  fu_erlauben = VALUES(fu_erlauben),
  fu_changed_uid = VALUES(fu_changed_uid),
  fu_changed_at = CURRENT_TIMESTAMP;

/* Read */
SELECT fu_erlauben FROM fv_mand_erl WHERE be_id = :be_id AND ei_id = :ei_id;

/* Update */
UPDATE fv_mand_erl
SET fu_erlauben = :fu_erlauben,
    fu_changed_uid = :fu_changed_uid
WHERE be_id = :be_id AND ei_id = :ei_id;

/* Delete */
DELETE FROM fv_mand_erl WHERE be_id = :be_id AND ei_id = :ei_id;



