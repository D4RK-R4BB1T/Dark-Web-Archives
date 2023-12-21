# Laravel Log Reader
[![Latest Stable Version](https://poser.pugx.org/jackiedo/log-reader/v/stable)](https://packagist.org/packages/jackiedo/log-reader)
[![Total Downloads](https://poser.pugx.org/jackiedo/log-reader/downloads)](https://packagist.org/packages/jackiedo/log-reader)
[![Latest Unstable Version](https://poser.pugx.org/jackiedo/log-reader/v/unstable)](https://packagist.org/packages/jackiedo/log-reader)
[![License](https://poser.pugx.org/jackiedo/log-reader/license)](https://packagist.org/packages/jackiedo/log-reader)

Laravel Log Reader is an easy log reader and management tool for Laravel. You can easily view, manage, and modify the log entries throught web browser and Artisan CLI. Using Laravel Log Reader is almost identical to using any Eloquent model.

# Features
- Allows using with Facade or using dependency injection (version 2.x only).
- Working via Artisan CLI.
- Manage the log files (list, delete).
- Manage all the log entries (paging for reading, sorting entries, count total, delete, mark as read).
- Filter the log entries (by the log file, environment, level).
- Access one log entry (to get its attribute, delete, mark as read).
- Allows to use your own log parser.

# Documentation
Look at [here](https://github.com/JackieDo/Laravel-Log-Reader/wiki) to learn more about Laravel Log Reader

# Screenshot
Here are some screenshots on how to use Laravel Log Reader in actual project. Keep in mind that this package does not include interface resources. Building user interfaces is up to each app developer.

#### Listing the log entries.
![index_collapsed](https://user-images.githubusercontent.com/9862115/27783200-76478934-5fff-11e7-86b8-ef74d2a202b7.png)

#### Expanding detail of one log entry.
![index_expanded](https://user-images.githubusercontent.com/9862115/27783212-7a9fce74-5fff-11e7-9ae8-25e9aed85a4f.png)

#### Working via Artisan CLI.
![artisan_get](https://user-images.githubusercontent.com/9862115/27825287-41de5fbc-60d9-11e7-9769-4b8ed33a3689.png)

![artisan_detail](https://user-images.githubusercontent.com/9862115/27825323-59fece38-60d9-11e7-8606-00144eed13b6.png)

# License
[MIT](LICENSE) © Jackie Do

# Thanks for use
Hopefully, this package is useful to you.