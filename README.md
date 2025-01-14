## What is TreoCore?
TreoCore is an open-source software ecosystem developed by TreoLabs GmbH, made for rapid development of web-based responsive business applications of any kind (ERP, PIM, CRM, DMS, MDM, DAM etc.). TreoCore is distributed under GPLv3 License and is free. It has a lot of features right out-of-the box and thus is an excellent tool for cost-effective and timely application development.

TreoCore is a single page application (SPA) with an API-centric, service-oriented architecture and flexible data model based on configurable entities and relations. You can organize any data and related business processes directly in TreoCore, many of them by simple and user-friendly configuration.

### For whom?
TreoCore is the best fit **for businesses**, who want to:
* solve custom business problems
* store data and organize business processes
* set up and use a middleware to connect with third-party systems
* create added value and best experience for your employees, customers and partners
* extend the functionality of the existing software infrastructure

### Which Applications based on TreoCore are available?
* TreoPIM
* TreoCRM

Both applications can be used in a single instance and can be extended with numerous modules.

### What is on board?

| Feature                                     | Description                                                                                                                                             |
|---------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| Dashboards                                  | Use multiple dashboards to control all main activities in the system.                                                                                   |
| Module Manager                              | It allows to install or to update any module directly from the administration panel, just choose the version you want to use.                           |
| Entity Manager                              | You can configure the data model directly from the administration panel, create new or edit existing entities and set relations of different types.     |
| Dynamic field logic                         | You can configure the conditions that make some fields invisible, read-only or editable.                                                                |
| Layout manager                              | Use it to configure any User Interface in the system or to show up the panels for related entities, via drag-and-drop.                                  |
| Label manager                               | You can edit any label in the system, in all languages you want to use.                                                                                 |
| Configurable navigation                     | Use drag-and-drop functionality to set a navigation as you wish, also separately for each user, if needed.                                              |
| Scheduled Jobs                              | You can configure, which jobs should be run by cron and at what schedule.                                                                               |
| Notifications                               | Set up a system or e-mail notifications for different events in the system.                                                                             |
| Data import and Export                      | You can import or export any data to any and from any entity in the system, even those you have just created.                                           |
| Advanced mass updates                       | Choose the entries to be updated, set the new values and do a bulk update.                                                                              |
| Advanced search and filters                 | You can configure the filters and search criteria as you wish, and save them, if you want to use this saved filter later.                               |
| Portals                                     | Use this additional layer to give access to third parties to your system. Use portal roles to restrict their access.                                    |
| Change Log and Stream                       | See all changes to the entries (who, old and new value, when) and add your own notices with a timestamp and attachments.                                |
| Queue Manager                               | Use it if you want to run or control processes in the background.                                                                                       |
| Access Control Lists (ACL)                  | Enterprise Level ACL based on Teams and Roles, with access level (own, team, all). You are able to edit the permissions even for each field separately. |
| REST API                                    | Integrate it with any third-party software, fully automated.                                                                                            |

### What are the advantages of using it?
* Really quick time to market and low implementation costs!
* Configurable, flexible and customizable
* Free - 100% open source, licensed under GPLv3
* REST API
* Web-based and platform independent
* Based on modern technologies
* Good code quality
* Service-oriented architecture (SOA)
* Responsive and user-friendly UI
* Configurable (entities, relations, layouts, labels, navigation)
* Extensible with modules
* Very fast
* Easy to maintain and support
* Many out-of-the-box features
* Best for Rapid Application Development

### What technologies is it based on?
TreoCore was created based on EspoCRM. It uses:

* PHP7 - pure PHP, without any frameworks to achieve the best possible performance,
* backbone.js - framework for SPA Frontend,
* Composer - dependency manager for PHP,
* Some libraries from Zend Framework 3,
* MySQL 5.

### Integrations
TreoCore has a REST API and can be integrated with any third-party system. You can also use import and export functions or use our modules (import feeds and export feeds) to get even more flexibility.

### Documentation
- We are working on documentation. The current version is available [here](https://treopim.com/help)
- Documentation for administrators is available [here](docs/en/administration/).

### Requirements

* Unix-based system. Linux Mint recommend
* PHP 7.1 or above (with pdo_mysql, openssl, json, zip, gd, mbstring, xml, curl,exif extensions)
* MySQL 5.5.3 or above

### Configuration instructions based on your server
* [Apache server configuration](docs/en/administration/apache-server-configuration.md)
* [Nginx server configuration](docs/en/administration/nginx-server-configuration.md)

### Installation
> Installation guide is based on **Linux Mint OS**. Of course, you can use any unix-based system, but make sure that your OS supports the following commands.<br/>

To create your new TreoCore application, first make sure you're using PHP 7.1 or above and have [Composer](https://getcomposer.org/) installed.

1. Create your new project by running:
   ```
   composer create-project treolabs/treocore my-treocore-project
   ```
   > **my-treocore-project** - project name
   
2. Change recursively the user and group ownership for project files. It is important for code generation mechanism
   ```
   sudo chown -R www-data:www-data my-treocore-project/
   ```
3. Make cron handler files executable:
   ```
   sudo chmod +x my-treocore-project/bin/cron.sh
   ```
4. Configure crontab:
   1. crontab should be configured for **www-data** user. You can do it by running:
      ```
      sudo crontab -u www-data -e
      ```
   2. put the following configuration:
      ```
      * * * * * cd /var/www/my-treocore-project; ./bin/cron.sh process-treocore /usr/bin/php 
      ```
      >**/var/www/my-treocore-project** - path to the project root <br/>
      >**process-treocore** - a unique id of process. You should use a different process id if you have several TreoCore projects in one server<br/>
      >**/usr/bin/php** - PHP7.1 or above
5. Install TreoCore following the installation wizard in web interface. Go to http://YOUR_PROJECT/

### License
TreoCore is published under the GNU GPLv3 [license](LICENSE.txt).

### Support

- TreoCore is a developed and supported by [TreoLabs GmbH](https://treolabs.com/).
- Feel free to join [our Community](https://community.treolabs.com/)
- To contact us please visit [our Website](https://treolabs.com/)
