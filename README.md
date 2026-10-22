# ggwebservices

Webservices for Exponential: serve and call JSON-RPC, XML-RPC, REST and SOAP, with a visual debugger in the
administration interface.

ggwebservices turns an Exponential installation into both

- a **webservices server**: expose your own PHP functions (and the functions you already registered for ezjscore)
  as JSON-RPC, XML-RPC, REST or SOAP services, with WSDL generated for SOAP, and with access control through roles
  and policies;
- a **webservices client**: call remote JSON-RPC, XML-RPC, REST, SOAP (with or without WSDL) and ezjscore servers
  from PHP code or straight from templates;

and adds a **WS Debugger** tab to the administration interface, where you can send any call to any server and see
the request and response in full.

A proxy view lets JavaScript in your pages call remote servers across domains, and even across protocols.

It is meant for developers who integrate Exponential with other systems, and for administrators who need to see what
a remote service really answers.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Permissions](#permissions)
- [Cron jobs](#cron-jobs)
- [Upgrading](#upgrading)
- [Known limits](#known-limits)
- [Troubleshooting](#troubleshooting)
- [Further documentation](#further-documentation)
- [Contributing](#contributing)
- [License](#license)
- [Credits and history](#credits-and-history)

## Requirements

- Exponential 6.x (the legacy kernel)
- PHP 8.0 or later, with the extensions for the protocols you use:
  - JSON-RPC and REST with JSON: `json` (always present in PHP 8);
  - XML-RPC: `xmlrpc`, which is a PECL extension since PHP 8.0;
  - SOAP and WSDL: `soap`;
  - REST with XML: `simplexml`;
  - optional: `curl` (HTTP 1.1 requests) and `zlib` (compressed requests and responses).
- Optional: the ezjscore extension, to expose ezjscore functions as webservices and to load the JavaScript helpers.

## Installation

### With Composer

The package is `se7enxweb/ggwebservices`. This maintained copy lives at
https://github.com/se7enxweb/ggwebservices; add it as a repository in your project's `composer.json` so Composer
takes the maintained releases:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/se7enxweb/ggwebservices" }
    ]
}
```

Then, in the root of your installation:

```bash
composer require se7enxweb/ggwebservices:^0.14.7
```

The legacy extension installer puts the files in `extension/ggwebservices`.

### Package name

Before 0.14.7 this extension was published as `gggeek/ggwebservices`, the name of Gaetano Giunta's original
package. Since 0.14.7 the maintained releases are published as `se7enxweb/ggwebservices`, which replaces
`gggeek/ggwebservices`, so the two are never installed side by side. To switch, run
`composer remove gggeek/ggwebservices && composer require se7enxweb/ggwebservices`, or change the line in the
`require` section of your `composer.json` to `"se7enxweb/ggwebservices": "^0.14.7"` and run
`composer update se7enxweb/ggwebservices`.

### By hand

1. Download a release from https://github.com/se7enxweb/ggwebservices/releases (or `git clone` the repository)
   and copy it to `extension/ggwebservices`. The directory must be called `ggwebservices`.
2. Activate the extension in `settings/override/site.ini.append.php`:

   ```ini
   [ExtensionSettings]
   ActiveExtensions[]=ggwebservices
   ```

3. Regenerate the autoload array and clear the caches, from the root of the installation:

   ```bash
   php bin/php/ezpgenerateautoloads.php -e
   php bin/php/ezcache.php --clear-all
   ```

4. If you use the client, add the log rotation cron job (see [Cron jobs](#cron-jobs)).

Log in to the administration interface: the **WS Debugger** tab is now in the top menu. In the admin4 design it
shows the `terminal` icon.

If logged-in users (for example Ajax calls from your pages) must send JSON-RPC, XML-RPC, REST or SOAP POST requests,
note that the ezformtoken extension rejects POST requests without its form token.

## Configuration

The extension reads `settings/wsproviders.ini`. Override it in `settings/override/wsproviders.ini.append.php` (or
in a siteaccess), never in the extension's own file.

### Server settings

```ini
[GeneralSettings]
# Log level of the webservices log (var/<site>/log/webservices.log):
# info, notice, debug, warning, error, critical or none
Logging=warning
# Size in bytes at which the log is rotated, and how many rotated files are kept
MaxLogSize=2097152
MaxLogrotateFiles=3
# With the jsonrpc.php / xmlrpc.php controllers: always use the default siteaccess (enabled),
# or the one matched by host, port ... (disabled)
UseDefaultAccess=enabled
# webservices/execute only: accept calls only from the IP addresses below
ValidateClientIPs=disabled
ValidClientIPs[]
# Protocols accepted for incoming calls
EnableJSONRPC=true
EnableXMLRPC=true
EnableREST=true
# Make the functions of every protocol directory available through every protocol
RegisterAllProtocolsFunctions=disabled
# Serve the functions registered for ezjscore as JSON-RPC / XML-RPC methods too
JscoreIntegration=enabled

[DebuggerSettings]
# YUI 2 version used by the debugger tree: the bundled 2.5.0, or "ezjscore"
PreferredVersion[yui2]=2.5.0

[ExtensionSettings]
# Extensions with a jsonrpc/, xmlrpc/ or rest/ directory holding an initialize.php
JSONRPCExtensions[]=ggwebservices
XMLRPCExtensions[]=ggwebservices
RESTExtensions[]=ggwebservices
```

SOAP is switched on by the kernel's `soap.ini`:

```ini
# settings/override/soap.ini.append.php
[GeneralSettings]
EnableSOAP=true
```

### Client settings: remote servers

Every remote server you call gets a block of its own. The block name is the name you use in PHP and templates.

```ini
# settings/override/wsproviders.ini.append.php

# A JSON-RPC server
[weather]
providerType=JSONRPC
providerUri=https://api.example.com/jsonrpc
Options[timeout]=30
Options[login]=apiuser
Options[password]=secret
# 1 basic, 2 digest, 4 GSS-Negotiate, 8 NTLM
Options[authType]=1

# A SOAP server described by a WSDL
[orders]
providerType=PhpSOAP
# empty: use the address in the WSDL
providerUri=
WSDL=https://erp.example.com/orders?wsdl
Options[soapVersion]=1

# A REST server returning JSON
[catalog]
providerType=REST
providerUri=https://shop.example.com/api
Options[method]=GET
Options[accept]=application/json
```

`providerType` is one of `JSONRPC`, `XMLRPC`, `REST`, `SOAP`, `PhpSOAP` or `eZJSCore`. The other options
(`requestCompression`, `acceptedCompression`, `forceCURL`, `cacheWSDL`, `nameVariable`, `responseType`,
`requestType`) and the per-server proxy settings (`ProxyServer`, `ProxyUser`, `ProxyPassword`) are described in
`settings/wsproviders.ini`.

## Usage

### Expose your own webservices

1. Create `extension/myext/jsonrpc/initialize.php` (or `xmlrpc/` or `rest/`) and register your functions on
   `$server`:

   ```php
   <?php
   $server->registerFunction(
       'fetchSyndicationFeedObjectList',  // name of the webservice and of the PHP function
       array( 'feedID' => 'integer' ),     // parameters (names are used for WSDL)
       'array',                            // return type
       'Returns a list of ...'             // help text
   );

   function fetchSyndicationFeedObjectList( $feedID )
   {
       if ( $feedID <= 0 )
       {
           return new ggWebservicesFault( -1, 'Invalid feed ID: negative value' );
       }
       return array( 'a', 'b', 'c' );
   }
   ```

2. Add the extension to the matching list in `wsproviders.ini.append.php`, for example
   `JSONRPCExtensions[]=myext`, and make sure the protocol is enabled (`EnableJSONRPC=true`).
3. Give the callers the `webservices/execute` policy (see [Permissions](#permissions)).
4. Clients call `https://www.example.com/webservices/execute/jsonrpc` (or `xmlrpc`, `rest`, `phpsoap`). The WSDL
   of a SOAP service is at `webservices/wsdl/<webservice>`, and `webservices/wsdl/<webservice>/(view)/html` shows
   it as a page.

```bash
curl -s -H 'Content-Type: application/json' \
     -d '{"method":"system.listMethods","params":[],"id":1}' \
     https://www.example.com/webservices/execute/jsonrpc
```

Built-in methods: `system.listMethods`, `system.methodHelp`, `system.methodSignature` and `system.multiCall`
(XML-RPC and JSON-RPC), and `ezp.authandexec( username, password, method, params )`, which logs a client in for
one call when it cannot keep a session cookie.

The files `jsonrpc.php` and `xmlrpc.php` are faster stand-alone controllers: copy them to the root of the
installation and allow them in your rewrite rules. They have **no** permission checks: every registered webservice
is open to everybody who can reach the URL.

### Call remote webservices from templates

```
{def $result = fetch( 'webservices', 'call', hash(
    'server', 'weather',
    'method', 'forecast',
    'parameters', array( 'Berlin' ),
    'options', hash( 'timeout', 10 ) ) )}
{$result|attribute( 'show' )}
{undef $result}
```

The template operators `washxml`, `washxmlcomment` and `washxmlcdata` escape values for XML text, comments and
CDATA sections. Turn the view cache off for pages that show live webservice results.

### Call remote webservices from PHP

```php
$result = ggeZWebServicesClient::call( 'weather', 'forecast', array( 'Berlin' ), array( 'timeout' => 10 ) );
```

### Call webservices from JavaScript

- Local server: `$.jsonrpc( method, params, options )` (jQuery) runs a JSON-RPC call on `webservices/execute/jsonrpc`.
- Remote server through the proxy: `$.wsproxy( servername, method, params, options )`, which goes through
  `webservices/proxy/jsonrpc/<servername>` (the remote server may use another protocol, such as SOAP).

Load them with ezjscore:

```
{ezscript_require( array( 'ezjsc::jquery', 'ggwstemplate::jquery::json', 'ggwstemplate::jquery::jsonrpc' ) )}
```

YUI 3 versions (`Y.io.jsonrpc`, `Y.io.wsproxy`, loaded with `ggwstemplate::yui3::jsonrpc`) exist too, but need a YUI 3
that the current ezjscore no longer ships. Examples are in [doc/samples](doc/samples).

### The debugger

Open the **WS Debugger** tab. Choose the protocol, the server address and the method, enter the parameters and send
the call: the debugger shows the HTTP request and response and the decoded result. It can list a server's methods
and their signatures, and it works with the servers you defined in `wsproviders.ini` as well as any other address.

Calls the debugger sends to this same installation arrive as the anonymous user, unless you add your session cookie
in the client options.

With `DebugOutput` and `DevelopmentMode` enabled, the requests sent by `fetch( 'webservices', 'call' )` also show
in the debug output, and `Logging=info` writes complete traces to `webservices.log`.

### Cache item

WSDL generated on the server is a cache item of its own, **webservices**:

```bash
php bin/php/ezcache.php --clear-id=webservices
```

## Permissions

The `webservices` module has these policy functions:

| Function | What it allows | Limitations |
|---|---|---|
| `execute` | Run incoming webservices through `webservices/execute`, see their WSDL/XSD | `Webservices` (single methods), `SiteAccess` |
| `proxy` | Use `webservices/proxy` from JavaScript | `RemoteServers` |
| `debugger` | Open the WS Debugger tab | |
| `wsdl` | See the WSDL of every webservice, not only of those the user may run | |

A good start for the Anonymous role is `execute` limited to `system.listMethods`, `system.methodHelp` and
`system.methodSignature`: visitors see what is available and run nothing. Note that `system.multiCall` runs any
webservice without checking the policies of the calls it contains. For clients that cannot log in, either set
`ValidateClientIPs=enabled` with their addresses in `ValidClientIPs[]`, or give Anonymous access to
`ezp.authandexec` and give the client a user account of its own. Details are in
[doc/authentication.rst](doc/authentication.rst).

## Cron jobs

The client writes `var/<site>/log/webservices.log`, which the kernel does not rotate. Run the `rotatewslogs` cron
part regularly:

```bash
php runcronjobs.php rotatewslogs
```

It rotates the log once it is larger than `MaxLogSize` and keeps `MaxLogrotateFiles` old files.

## Upgrading

Replace the `extension/ggwebservices` directory (or run `composer update se7enxweb/ggwebservices`), regenerate the
autoloads and clear the caches, as in step 3 of the installation. Keep your settings in override files. The changes
of each release are in [doc/changelogs](doc/changelogs).

## Known limits

- WSDL support is limited, and so is SOAP 1.2.
- JSON-RPC is version 1.0 only.
- The client does not manage cookies automatically and does not support OAuth.
- The proxy speaks XML-RPC and JSON-RPC to the browser, so it cannot express REST verbs such as POST.
- REST support follows RPC semantics: client parameters go into the query string or the body, not into the URL
  path, and the server has no routing of path parameters.
- More ideas are listed in [doc/todo](doc/todo).

## Troubleshooting

- **The WS Debugger tab is missing.** The user lacks the `webservices/debugger` policy, or the extension is not
  active. Clear the INI cache (`php bin/php/ezcache.php --clear-tag=ini`).
- **"Disabled protocol"** from `webservices/execute`: set `Enable<PROTOCOL>=true` in `wsproviders.ini` (SOAP: in
  `soap.ini`).
- **Access denied for a call.** The caller's role lacks `webservices/execute` for that method; calls without a session
  run as Anonymous.
- **POST calls of logged-in users fail.** See the ezformtoken note in the installation.
- **Logs.** Raise `Logging` to `info` and read `var/<site>/log/webservices.log`.

## Further documentation

- [doc/usage.rst](doc/usage.rst): server and client set-up step by step
- [doc/api.rst](doc/api.rst): modules, views, fetch functions, operators and built-in webservices
- [doc/authentication.rst](doc/authentication.rst): authentication schemes for clients and servers
- [doc/soap_readme.txt](doc/soap_readme.txt): SOAP questions and answers
- [doc/samples](doc/samples): clients for real services, Ajax and template examples, an `initialize.php` example
- [INSTALL](INSTALL): the short installation notes

## Contributing

Bug reports and pull requests are welcome at https://github.com/se7enxweb/ggwebservices. Please keep the code PHP 8
compatible and run `php -l` on every file you change.

## License

GNU General Public License v2.0 (or any later version). See [LICENSE](LICENSE). The extension includes YUI 2.5.0
(BSD license), phpxmlrpc 3.0.0 (BSD license) and the jQuery JSON plugin (MIT license).

## Credits and history

ggwebservices was written by Gaetano Giunta from 2009 to 2022 for eZ Publish, the content management system the
Exponential legacy kernel grew out of, and was published as `gggeek/ggwebservices`. Its contributors added the French
and Italian translations and many fixes. 7x maintains it for Exponential 6.x.
