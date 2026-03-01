Die Klasse VF_Database ist eine PHP-Datenbank-Wrapper-Klasse, die auf PDO (PHP Data Objects) basiert und speziell für eine modulare Anwendung mit Mandanten- und Rollenverwaltung konzipiert ist. Sie dient als zentrale Schnittstelle für den Datenbankzugriff und bietet eine strukturierte, sichere und wiederverwendbare Möglichkeit, mit den verschiedenen Datenbanktabellen zu interagieren.

Hauptmerkmale und Funktionen der Klasse VF_Database
1. PDO-Verbindung und Konfiguration
Die Klasse stellt eine PDO-Verbindung zur MySQL-Datenbank her, wobei Verbindungsparameter (Host, Datenbankname, Benutzer, Passwort) aus einer Konfigurationsdatei (VF_Config.lib.php) geladen werden.
PDO-Optionen sind so gesetzt, dass Fehler als Ausnahmen geworfen werden, Standard-Fetch-Modus ist assoziatives Array, und Prepared Statements werden nativ verwendet (keine Emulation).
Ein Tabellenpräfix (z.B. fv_) wird verwendet, um Tabellen logisch zu gruppieren und Mandantentrennung zu unterstützen.
2. Tabellen-Whitelist und Validierung
Die Klasse verwaltet eine Whitelist erlaubter Tabellen, um SQL-Injection durch dynamische Tabellennamen zu verhindern.
Tabellenpräfix und Spaltennamen werden streng auf erlaubte Zeichen geprüft.
Dadurch wird sichergestellt, dass nur definierte Tabellen und Spalten angesprochen werden können.
3. Generische CRUD-Methoden
Select: Flexible Auswahl von Datensätzen mit optionalen WHERE-Bedingungen, Spaltenauswahl, Sortierung, Limit und Offset.
SelectOne: Auswahl eines einzelnen Datensatzes.
Insert: Einfügen neuer Datensätze mit automatischer Bindung von Parametern.
Update: Aktualisierung von Datensätzen mit WHERE-Bedingungen.
Delete: Löschen von Datensätzen mit WHERE-Bedingungen (immer mit Sicherheitsprüfung, dass WHERE nicht leer ist).
4. Transaktionsmanagement
Die Klasse bietet eine Methode, um mehrere Datenbankoperationen innerhalb einer Transaktion auszuführen, sodass alle Operationen entweder komplett ausgeführt oder bei Fehlern zurückgerollt werden.
5. Spezialisierte Methoden für Benutzer- und Mandantenverwaltung
Methoden zur Verwaltung von Benutzern, Passwörtern, Rollen und Mandantenrechten.
Passwortverwaltung mit sicherer Hash-Überprüfung.
Rollenzuweisung und Abfrage von Benutzerrollen.
Mandantenrechte mit differenzierten Berechtigungen (lesen, update, nix).
6. Erweiterungen für weitere Tabellen
Verwaltung von Admin-Mail-Gruppen, Benutzerdaten, Mandantenstammdaten, Modulen.
Verwaltung von Archivordnungen, Sammlungen, Firmen, Abkürzungen, Bundesländern und Staaten.
Synchronisation von Sammlungskürzeln über eine zentrale Mastertabelle.
7. Sicherheitsaspekte
Strikte Validierung von Eingaben für Tabellen- und Spaltennamen.
Verwendung von Prepared Statements zur Vermeidung von SQL-Injection.
Einsatz von Foreign Key Constraints in der Datenbank zur Sicherstellung der referenziellen Integrität.
Zweck und Nutzen
Die Klasse VF_Database ist das Rückgrat der Datenbankkommunikation in einer komplexen, mandantenfähigen Anwendung mit differenzierten Benutzer- und Rollenrechten. Sie abstrahiert die Datenbankzugriffe, sorgt für Sicherheit und Datenintegrität und erleichtert die Wartung und Erweiterung der Anwendung durch klar strukturierte Methoden und zentrale Verwaltung von Stammdaten.