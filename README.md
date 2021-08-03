Cambridge air photos
====================

This is a PHP application which presents the Department of Geography's CUCAP catalogue.

https://www.cambridgeairphotos.com/


Screenshot
----------

![Screenshot](screenshot.png)


Usage
-----

1. Clone the repository.
2. Download the library dependencies and ensure they are in your PHP include_path.
3. Download and install the famfamfam icon set in /images/icons/
4. Add the Apache directives in httpd.conf (and restart the webserver) as per the example given in .httpd.conf.extract.txt; the example assumes mod_macro but this can be easily removed.
5. Create a copy of the index.html.template file as index.html, and fill in the parameters.
6. Access the page in a browser at a URL which is served by the webserver.


Dependencies
------------

* [application.php application support library](https://download.geog.cam.ac.uk/projects/application/)
* [csv.php CSV manipulation library](https://download.geog.cam.ac.uk/projects/csv/)
* [ConversionsLatLong Lat/lon conversion library](https://github.com/geograph-project/geoportal/blob/master/includes/conversionslatlong.class.php)
* [database.php database wrapper library](https://download.geog.cam.ac.uk/projects/database/)
* [frontControllerApplication.php front controller application implementation library](https://download.geog.cam.ac.uk/projects/frontcontrollerapplication/)
* [multisearch.php Multisearch library](https://download.geog.cam.ac.uk/projects/multisearch/)
* [pagination.php Pagination library](https://download.geog.cam.ac.uk/projects/pagination/)
* [pureContent.php general environment library](https://download.geog.cam.ac.uk/projects/purecontent/)
* [FamFamFam Silk Icons set](http://www.famfamfam.com/lab/icons/silk/)


Author
------

Martin Lucas-Smith, Department of Geography, University of Cambridge, 2011-21.


License
-------

GPL3.

