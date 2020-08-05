# TYPO3 Extension ``md_calendarize_frontend``

This extension enables a frontend user to add ``ext:calendarize``-records in the frontend.

Templates are ready to use with the [bootstrap framework](https://getbootstrap.com/) and icons will be shown, if you have [Font Awesome](https://fontawesome.com/) icon set included in your project.

## Requirements

- TYPO3 >= 9.5
- ext:calendarize >= 6.0

## Installation

- Install the extension by using the extension manager or use composer
- Include the static TypoScript of the extension
- Configure the extension by setting your own typoscript variables
    - `dateFormat`: Format of date input fields (see [PHP date function](https://www.php.net/manual/de/function.date.php))
    - `dateFormatPlaceholder`: Placeholder for date form fields
    - `timeFormat`: Format of time input fields (see [PHP date function](https://www.php.net/manual/de/function.date.php))
    - `timeFormatPlaceholder`: Placeholder for time fields
    - `parentCategory`: If you want to use categories for your calendar entries, set the ID of the category which child items should be displayed

## Usage

- Add the pluign ``Calendarize frontend`` on a page, which is restricted by the frontend user login
- Select a storage page in the plugin-tab in the field ``Record Storage Page``
- Make sure to include `jQuery` in the header of the website in order to get some magic in the template working
- Now a frontend user is able to add, edit and delete own records

## Bugs and Known Issues
If you find a bug, it would be nice if you add an issue on [Github](https://github.com/cdaecke/md_calendarize_frontend/issues).

# THANKS

Thanks a lot to all who make this outstanding TYPO3 project possible!

## Credits

- Extension icon was taken from [Freepik](https://www.flaticon.com/authors/freepik).
