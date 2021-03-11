# Auto MySQL Backup
Welcome to this quick setup guide for automating your database backups to Dropbox!
Whether you have an established site or are starting from scratch, this guide will
give you the automation tool you need to keep your database safe. You can rest
easy knowing your database is safely backed up to your Dropbox as often as you
want for free.

For this guide, some familiarity in regards to UNIX/Linux is preferred,
however I've included cPanel screenshots and videos to help even a beginner
get set up.

![Hero image](.repository/hero.png?raw=true)

## How does it work?
The script works to create a backup of your existing database of choice, zipping it,
breaking it up into smaller chunks, and uploading those chunks to Dropbox,
where it is automatically re-assembled into a single file.

## Who uses this?
I use this automation for my [personal blog](https://advaitsaravade.me), my personal projects
[Students App](https://students.app), [Climate Simple](https://climatesimple.com), as well as my company and app databases
[Deep Meditate](https://deepmeditate.com), [Heltapp](https://heltapp.com). These databases are in the GBs when compressed,
and I’ve used backups from this tool on numerous occasions when a database server
failed. I hope you can learn from my experience of seeing a server fail without backup,
without having to go through it yourself. This tool has helped me sleep easy.

## Is this free?
Yes. Unlike "backup solutions" like Snapshooter.io or others, you don't have to pay a monthly
fee for this software. With Auto MySQL backup your database is protected, automatic
scheduling is as-often-as-you-want, and you have a 30-day history of your backups
in your Dropbox.

## Instructions
Without ado, let’s dive in and get you setup. Setting up Auto MySQL Backup is simple.
We'll start by:
1. Download script from Github
2. Create a database user for backups
3. Create a Dropbox token for your account
4. Configure the backup script
5. Upload the backup file and working directory to your server
6. Setup the Cron job to schedule backups


### Download script from Github
If you haven’t already, go ahead and download the code used in
[this guide from Github](https://github.com/advaitsaravade/Auto-MySQL-Backup). You can
use the following command to download the repository to your machine:

`git clone https://github.com/advaitsaravade/Auto-MySQL-Backup.git`

### Create a database USER for backups
To get started, create a database USER meant solely for backups. You can do this in cPanel or a shell.
Make note of the username, the password you give it. Once the user is created, give it permission
to access your database(s) by assigning it the following privileges:

1. SELECT
2. SHOW VIEW (If any database has Views)
3. TRIGGER (If any table has one or more triggers)
4. LOCK TABLES (If you use an explicit --lock-tables)

Make a note of your database user’s username, and password.

### Create a Dropbox token for your account
Go to Dropbox Developer and create an app for your account.

![Dropbox 1](.repository/aDHGn9nc.png?raw=true)

Once you create an app, you need to generate an access token for it.

![Dropbox 2](.repository/dIaA614c.png?raw=true)
Open the app dashboard page, scroll to the OAuth 2 header, set the Access token
expiration to “No expiration” and click the Generate button. Note down your Dropbox
app’s access token.

### Configure the backup script
Now we have everything we need to setup the backup automation. Open the backup.php file from the
Github download into a text editor. At the very top of the script, enter the values you noted
from earlier steps into the variables.

![Code screenshot](.repository/rYei_zU4.png?raw=true)

Enter the following values:
1. `database_host`: The IP address or hostname (usually localhost) for your database.
2. `database_user`: The username of the database user we setup previously.
3. `database_pass`: The password of the database user we setup previously.
4. `this_directory`: The absolute path of the folder where you will upload this script. For my personal server it was `/home/advait/advaitsaravade.me/cron/auto_mysql_backup/`. I already had a `cron` folder for my other automations, so I created a new `auto_mysql_backup` folder there, and added these files there.
5. `dropbox_token`: The Dropbox token you generated previously.

### Upload the backup file and working directory to your server
Upload the backup.php file and the working directory backup_files/ to your server. Make note of the absolute location of the folder you uploaded it to and ensure it matches the value of the this_directory variable from the previous step.

### Setup the Cron job to schedule backups
Select how often you want your backup to occur, and how many of your databases you wish to backup.
Setup a Cron job for each database, and a separate `backup.php` script for each database as well.
For example, if you’re backing up twice a day, and you’re using cPanel, your screen should look
something like this. Please consult your cPanel/hosting manual for setting up Cron jobs properly.

![cPanel Cron Job](.repository/b11UA3tI.png?raw=true)

If you run into any questions... send me a tweet ([@AdvaitSaravade](https://twitter.com/AdvaitSaravade)) and let me know!
I want to make this guide as useful as possible for you.

## Technical footnotes
- This version of the script works with v2 of the Dropbox API. It uses multi-part
uploading to ensure every upload is successful, no-matter the size of the database.
- For most distributions, the `split` function using in `backup.php` will create
00 post-fixed chunk files. However, some distributions will use a-z endings like
databaseChunkaa, databaseChunkab… Please modify the script appropriately.
- This guide and following code is provided as-is. It is your responsibility to
ensure compatibility, and safe use.

## Give a star! :star:
If you like or are using this project to backup your databases, please give it a star! Thanks!
