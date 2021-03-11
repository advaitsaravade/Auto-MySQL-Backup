<?php

/* Author: Advait Saravade (https://advaitsaravade.me)
 * Dependencies on local server: zip (For Debian/Ubuntu: apt-get install zip unzip)
 * Database user requires the following privilegs: SELECT, SHOW VIEW (If any database has Views), TRIGGER (If any table has one or more triggers), LOCK TABLES (If you use an explicit --lock-tables)
 */

// Config
/* CHANGE VALUES BELOW */
$dropbox_token = "dropbox_token_here";
$database_host = "localhost"; // Don't change unless you know what you're doing
$database_name = "database_name_here"; // Fill in the name of your database here
$database_user = "database_user_here"; // Fill in the user of your database here
$database_pass = "database_user_password_here"; // Fill in the password of your database user here

// The absolute location of the *directory* where this file is located on your server, followed by a "/"
// For example if you're storing this script in the public_html directory, then it could be ""/var/www/site_name/public_html/auto_mysql_backup/""
$this_directory = "this_directory_here";
/* DO NOT CHANGE BELOW THIS */

$local_working_directory = $this_directory . "backup_files/";
$dropbox_working_directory = "/".$database_name."/"; // Location on the Dropbox App folder you want to upload the backup file to

echo "Backup process started.\n";

// Create MySQL backup file
$datetime = date("d-h_i_A", time());
$file_name = $database_name . "_date_" . $datetime;
$file_path = $local_working_directory . $file_name;
$sql_file = $file_path . ".sql";
exec("mysqldump --user=" . $database_user . " --password=" . $database_pass . " --host=" . $database_host . " " . $database_name . " > " . $sql_file);
echo "MySQL logical backup file created.\n";

// Compress the SQL file
$zip_file = $file_path . ".zip";
exec("cd " . $local_working_directory . "; zip " . $file_name . ".zip " . $file_name . ".sql");
echo "Backup file successfully compressed.\n";

// Split the zip file
$chunk_size_mb = 24;
$chunk_name = "databaseChunk";
$chunk_files = array();
exec("split -d -b " . $chunk_size_mb . "m " . $zip_file . " " . $local_working_directory . $chunk_name);
$stat = stat($zip_file);
$chunk_count = ceil($stat['size'] / ($chunk_size_mb*1024*1024));
echo "Compressed file successfully broken up into " . $chunk_count . " chunks.\n";

// Backup file to dropbox using multipart upload
$session_id = "";
for($i = 0; $i < $chunk_count; $i++) {
    if($i <= 9) {
        $additionalZero = "0";
    } else {
        $additionalZero = "";
    }
    $chunkFile = $local_working_directory . $chunk_name . $additionalZero . $i;
    $fp = fopen($chunkFile, 'rb');
    $size = filesize($chunkFile);
    if($i == 0) {
        if($chunk_count == 1) {
            $ch = curl_init('https://content.dropboxapi.com/2/files/upload');
            $cheaders = array('Authorization: Bearer '.$dropbox_token,
                  'Content-Type: application/octet-stream',
                  'Dropbox-API-Arg: {"path": "' . $dropbox_working_directory . $file_name . '.zip", "mode": "overwrite", "autorename": false}');
            echo "Single file uploaded.\n";
        } else {
            $ch = curl_init('https://content.dropboxapi.com/2/files/upload_session/start');
            $cheaders = array('Authorization: Bearer '.$dropbox_token,
                              'Content-Type: application/octet-stream',
                              'Dropbox-API-Arg: {"close": false}');
            echo "Upload started.\n";
        }
    } elseif(($i+1) == $chunk_count) {
        $ch = curl_init('https://content.dropboxapi.com/2/files/upload_session/finish');
        $cheaders = array('Authorization: Bearer '.$dropbox_token,
                          'Content-Type: application/octet-stream',
                          'Dropbox-API-Arg: {"cursor": {"session_id": "' . $session_id . '", "offset": ' . $filesizeUploadedSoFar . '}, "commit": {"path": "' . $dropbox_working_directory . $file_name . '.zip", "mode": "overwrite", "autorename": false}}');
        echo "Upload complete.\n";
    } else {
        $ch = curl_init('https://content.dropboxapi.com/2/files/upload_session/append_v2');
        $cheaders = array('Authorization: Bearer '.$dropbox_token,
                          'Content-Type: application/octet-stream',
                          'Dropbox-API-Arg: {"cursor": {"session_id": "' . $session_id . '", "offset": ' . $filesizeUploadedSoFar . '}, "close": false}');
        echo "Uploaded chunk file - " . $chunk_name . $additionalZero . $i . ".\n";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $cheaders);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, $size);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if($i == 0 && $chunk_count >= 2) {
        // Get session ID from 'start' command response
        $json = json_decode($response, true);
        $session_id = $json['session_id'];
    }
    curl_close($ch);
    fclose($fp);
    $filesizeUploadedSoFar += $size;
    array_push($chunk_files, $chunkFile);
}
// Cleanup
echo "Cleaning up residual files.\n";
exec("rm " . $sql_file . "; rm " . $zip_file . ";\n");
foreach ($chunk_files as $chunk_file) {
  exec("rm " . $chunk_file . ";");
}
echo "Backup process complete.\n"
?>
