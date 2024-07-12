---
title: Available Helper Methods
sidebar_position: 2
---

This package provides the following helper methods: 

- **`default_setting(string $key)`**: Returns a default setting value from the `config\defaults.php` file.
- **`get_setting(string $key)`**: Returns the value of the save setting for the given setting key. If a value is not saved, returns the default value for the setting.
- **`set_file_setting(string $key, string $path = 'storage/uploads')`**: Saves an uploaded file from the current request to the given path and sets the setting value to the uploaded file url. Note that the uploaded file must have the same input name as the setting key. If the request has an empty value for the input name, then the setting would be reset to its default value.
- **`reset_file_setting(string $key)`**: Deletes the saved file for the given file setting and resets its value to its default value. 

