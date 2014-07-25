## Laravel service provider for Understand.io

### Introduction

This packages provides a full abstraction for Understand.io and provides extra features to improve Laravel's default logging capabilities. It is essentially a wrapper around Laravel's event handler to take full advantage of Understand.io's data aggregation and analysis capabilities.

### Quick start

1. Add this package to your composer.json and run `composer update`:

    ```php
    "understand/understand-laravel": "1.*"
    ```

2. Add the ServiceProvider to the providers array in app/config/app.php
  
    ```php
    'Understand\UnderstandLaravel\UnderstandLaravelServiceProvider',
    ```

3. Publish configuration file

    ```
    php artisan config:publish understand/understand-laravel
    ```

4. Set your input key in config file
  
    ```php
    'token' => 'my-input-token'
    ```

### How to send events/logs

#### Laravel logs
By default, Laravel automatically stores its logs in ```app/storage/logs```. By using this package, your logs can also be sent to your Understand.io channel. This includes error and exception logs, as well as any log events that you have defined (for example, ```Log::info('my custom log')```).


#### Eloquent model logs
Eloquent model logs are generated whenever one of the `created`, `updated`, `deleted` or `restored` Eloquent events is fired. This allows you to automatically track all changes to your models and will contain a current model diff (`$model->getDirty()`), the type of event (created, updated, etc) and additonal meta data (user_id, session_id, etc). This means that all model events will be transformed into a log event which will be sent to Understand.io.
 
By default model logs are disabled. To enable model logs, you can set the config value to `true`:

```php 
'eloquent_logs' => true,
```

### Additional meta data (field providers)
You may wish to capture additional meta data with each event. For example, it can be very useful to capture the request url with exceptions, or perhaps you want to capture the current user's ID. To do this, you can specify custom field providers via the config.

```php
/**
 * Specify additional field providers for each log
 * E.g. sha1 version session_id will be appended to each "Log::info('event')"
 */
'additional' => [
    'model_log' => [
        'session_id' => 'UnderstandFieldProvider::getSessionId',
        'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
        'user_id' => 'UnderstandFieldProvider::getUserId',
        'env' => 'UnderstandFieldProvider::getEnvironment',
        'client_ip' => 'UnderstandFieldProvider::getClientIp',
    ],
    'laravel_log' => [
        'session_id' => 'UnderstandFieldProvider::getSessionId',
        'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
        'user_id' => 'UnderstandFieldProvider::getUserId',
        'env' => 'UnderstandFieldProvider::getEnvironment',
    ],
    'exception_log' => [
        'session_id' => 'UnderstandFieldProvider::getSessionId',
        'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
        'user_id' => 'UnderstandFieldProvider::getUserId',
        'env' => 'UnderstandFieldProvider::getEnvironment',
        'url' => 'UnderstandFieldProvider::getUrl',
        'method' => 'UnderstandFieldProvider::getRequestMethod',
        'client_id' => 'UnderstandFieldProvider::getClientIp',
        'user_agent' => 'UnderstandFieldProvider::getClientUserAgent'
    ]
]
```

The Understand.io service provider contains a powerful field provider class which provides default providers, and you can create or extend new providers.

```php
dd(UnderstandFieldProvider::getSessionId()); 
// output: c624e355b143fc050ac427a0de9b64eaffedd606
```

#### Default field providers
The following field providers are included in this package:

- `getSessionId` - return sha1 version of session id
- `getRouteName` - return current route name (e.g. `clients.index`).
- `getUrl` - return current url (e.g. `/my/path?with=querystring`).
- `getRequestMethod` - return request method (e.g. `POST`).
- `getServerIp` - return server IP.
- `getClientIp` - return client IP.
- `getClientUserAgent` - return client's user agent.
- `getEnvironment` - return Laravel environment (e.g. `production`).
- `getProcessIdentifier` - return unique token which is unique for every request. This allows you to easily group all events which happen in a single request.
- `getUserId` - return current user id. This is only available if you make sure of the default Laravel auth or the cartalyst/sentry package. Alternatively, if you make use of a different auth package, then you can extend the `getUserId` field provider and implement your own logic.

#### How to extend create your own methods or extend the field providers
```php

UnderstandFieldProvider::extend('getMyCustomValue', function()
{
    return 'my custom value';
});

UnderstandFieldProvider::extend('getCurrentTemperature', function()
{
    return \My\Temperature\Provider::getRoomTemperature();
});

```

#### Example
Lets assume that you have defined a custom field provider called `getCurrentTemperature` (as above). You should then add this to your config file as follows:

```php
    'laravel_log' => [
        'meta' => [
            ...
            'UnderstandFieldProvider::getCurrentTemperature',
            ...
        ]
    ],
```

This additional meta data will then be automatically appended to all of your Laravel log events (`Log::info('my_custom_event')`), and will appear as follows:

```json

{
  "message": "my_custom_event",
  "custom_temperature":"23"
}
```


### How to send data asynchnoously
By default each log event will be sent to Understand.io's api server directly after the event happens. If you generate a large number of logs, this could slow your app down and, in these scenarios, we recommend that you make use of a queue handler. To do this, change the config parameter `handler` to `queue` and Laravel queues will be automatically used. Bear in mind that by the default Laravel queue is `sync`, so you will still need to configure your queues properly using something like iron.io or Amazon SQS. See http://laravel.com/docs/queues for more information. 

```php
/**
 * Specify which handler to use (sync|queue)
 */
'handler' => 'queue',
```

### Configuration

```php

<?php

return [

    /**
     * Input key
     */
    'token' => 'd9048b69-15fe-4da1-a623-d83cdc87e887',

    /**
     * Api server endpoint for http transport
     */
    'url' => 'localhost:3000',

    /**
     * Specifies whether logger should throw an exception of issues detected
     */
    'silent' => false,

    /**
     * Specify which handler to use (sync|queue)
     */
    'handler' => 'sync',

    /**
     * Send all laravel logs to understnad.io (e.g. "Log::info('my event')")
     */
    'laravel_logs' => true,

    /**
     * Send all Eloquent model events and changes to understand.io
     */
    'eloquent_logs' => false,

    /**
     * Specify additional field providers for each log
     * E.g. sha1 version session_id will be appended to each "Log::info('event')"
     */
    'additional' => [
        'model_log' => [
            'session_id' => 'UnderstandFieldProvider::getSessionId',
            'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
            'user_id' => 'UnderstandFieldProvider::getUserId',
            'env' => 'UnderstandFieldProvider::getEnvironment',
            'client_ip' => 'UnderstandFieldProvider::getClientIp',
        ],
        'laravel_log' => [
            'session_id' => 'UnderstandFieldProvider::getSessionId',
            'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
            'user_id' => 'UnderstandFieldProvider::getUserId',
            'env' => 'UnderstandFieldProvider::getEnvironment',
        ],
        'exception_log' => [
            'session_id' => 'UnderstandFieldProvider::getSessionId',
            'request_id' => 'UnderstandFieldProvider::getProcessIdentifier',
            'user_id' => 'UnderstandFieldProvider::getUserId',
            'env' => 'UnderstandFieldProvider::getEnvironment',
            'url' => 'UnderstandFieldProvider::getUrl',
            'method' => 'UnderstandFieldProvider::getRequestMethod',
            'client_id' => 'UnderstandFieldProvider::getClientIp',
            'user_agent' => 'UnderstandFieldProvider::getClientUserAgent'
        ]
    ]
];
```

### License

The Laravel Understand.io service provider is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)