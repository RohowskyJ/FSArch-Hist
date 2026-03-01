// Zugriff auf Metadaten-Maps (statt globale Arrays):
$nullableMap   = $meta->getNullableMap();
$commentsMap   = $meta->getCommentsMap();
$stylesMap     = $meta->getStylesMap();
$typesMap      = $meta->getTypesMap();
$tableNamesMap = $meta->getTableNamesMap();
$maxLenMap     = $meta->getMaxLengthsMap();
Diese Erweiterung ermöglicht es dir, bequem alle Spalten mehrerer Tabellen (z.B. bei Joins) auszulesen und die Metadaten weiterhin übersichtlich zu verwalten.

Beispiel für die Verwendung mit mehreren Tabellen (z.B. Join-Tabellen):
// $dblink ist dein mysqli-Verbindungsobjekt
$databaseName = 'deine_datenbank';
$debug = true;

$meta = new TableColumnMetadata($dblink, $databaseName, $debug);

// Beispiel: mehrere Tabellen (Join-Tabellen)
$joinTables = ['tabelle1', 'tabelle2', 'tabelle3'];

$columnsByTable = $meta->getColumnsForTables($joinTables);

foreach ($columnsByTable as $table => $columns) {
    echo "Tabelle: $table\n";
    foreach ($columns as $col) {
        echo " - $col\n";
    }
    echo "\n";
}