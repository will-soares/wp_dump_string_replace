# wp_dump_string_replace
PHP script for strings replacement in Wordpress dumps. Useful to move production database to DEV and STG environments.

Well, this has been implemented after uncountable issues moving Wordpress websites from Production to Dev and Staging
environments, which means a lot of serialized content with absolute URLs that have to be replaced when doing it.

This is for my personal use, but any thoughts are welcome. :)

Will Soares


# Running the script

Make sure you have PHP installed locally. Go to your Terminal and run:

```
php replace.php search_string replace_string file_dump_path [... output_file_path]
```

**Example**
This will generate and SQL file named `your-dump_output.sql` in `path/to/`
```
php replace.php www.example.com www.staging.example.com path/to/your-dump.sql
```
In case you set the optional parameter, then the file will be created wherever you want:

```
php replace.php www.example.com www.staging.example.com path/to/your-dump.sql /Desktop/your-new-dump.sql
```