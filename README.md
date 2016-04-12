# Pancakes

Terminus Plugin to open any Pantheon site database using a SQL GUI client.

## Supported:
   [HeidiSQL](http://www.heidisql.com/) (Windows)

   [Sequel Pro](http://www.sequelpro.com/) (Mac)

   [MySQL Workbench](https://dev.mysql.com/downloads/workbench/) (Mac, Linux and Windows)

## Examples:
`terminus site heidisql`

`terminus site heidi --site=my-company --env=dev`

`terminus site sequelpro`

`terminus site sequel --site=my-company --env=dev`

`terminus site mysql-workbench`

`terminus site workbench --site=my-company --env=dev`

## Installation:
For help installing, see [Terminus's Wiki](https://github.com/pantheon-systems/terminus/wiki/Plugins)

## A Note About Windows:
The plugin will automatically attempt to find the HeidiSQL executable within your `Program Files` directory.  If your version of HeidiSQL is installed in a non-standard location or you are using the portable version of HeidiSQL, ensure the full path to heidisql.exe (including the executable itself) is set in the `TERMINUS_PANCAKES_HEIDISQL_LOC` environment variable.

## Help:
Run `terminus help site pancakes` for help.
