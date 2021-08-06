INTRODUCTION
------------

Reference Table Formatter provides a field formatter to render a table of
referenced entities' fields on the target entity of a variety of different
reference field types.

Currently supported field types are:

 * Entity Reference from core
 * Entity Reference Revisions (such as for Paragraphs)
 * Field Collection (support deprecated, not selectable in UI)


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Ensure Drupal core's Field UI module is enabled.
    2. Navigate to the relevant entity's (node, media, taxonomy, etc. ) field
       display configuration form.
    3. For any entity reference or entity reference revision fields, select
       “Table of Fields” type.
    4. Optionally modify the options for the formatter.
    5. Hit save.


TROUBLESHOOTING
---------------

 * Error: "Using non-default reference handler with reference_table_formatter
   has not yet been implemented":

   - The current implementation of the formatter requires that the field uses
     the "Default" reference method in the field's setting. This limitation is
     planned to be resolved in version 2.0.
