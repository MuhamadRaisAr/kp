<?php
require_once 'includes/koneksi.php';

$result = mysqli_query($koneksi, "DESCRIBE kelas");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . "\n";
}
?>
