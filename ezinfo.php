<?php
/**
 * About-page information for the ggwebservices extension.
 *
 * @copyright (C) 2009-2022 Gaetano Giunta, (C) 2026 7x
 * @license GNU General Public License v2.0 (or any later version)
 */

class ggwebservicesInfo
{
    static function info()
    {
        return array(
            'Name' => "GG Webservices extension",
            'Version' => "0.14.7",
            'Copyright' => "Copyright (C) 2009-2022 Gaetano Giunta, Copyright (C) 2026 7x",
            'License' => "GNU General Public License v2.0 (or any later version)",
            'Info_url' => "https://github.com/se7enxweb/ggwebservices",
            'Includes the following third-party software' => array(
                'Name' => '<a href="http://developer.yahoo.com/yui">YUI</a>',
                'Version' => "2.5.0",
                'Copyright' => 'Copyright (c) 2010, Yahoo! Inc. All rights reserved.',
                'License' => 'Licensed under the BSD License' ),
            'Includes the following third-party software (2)' => array(
                'Name' => '<a href="https://github.com/gggeek/phpxmlrpc">phpxmlrpc</a>',
                'Version' => "3.0.0",
                'Copyright' => 'Copyright (c) 1999,2000,2002 Edd Dumbill.',
                'License' => 'Licensed under the BSD License' ),
            'Includes the following third-party software (3)' => array(
                'Name' => '<a href="http://code.google.com/p/jquery-json/">jQuery JSON Plugin</a>',
                'Version' => "2.1",
                'Copyright' => 'Brantley Harris (?)',
                'License' => 'MIT License' )
                /*'nuSOAP' => array(
                    'Version' => '2008-04-06',
                    'License' => 'GNU/LGPL  v2.1 - Copyright (c) 2002 NuSphere Corporation',
                    'For more information' => 'http://sourceforge.net/projects/nusoap' ),*/
        );
    }
}
