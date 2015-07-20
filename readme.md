# Splyt PHP SDK Integration Guide

<h2>Initialization</h2>

1. Place the contents of the SDK include folder under your webroot or a folder accessible to your PHP distribution.  Insure that you have Splyt.php, any Splyt_*.php plugins you might like, and the ssf folder included with the distribution.
2. The Splyt SDK has a dependency on <a href="http://pear.php.net/package/XML_Serializer">XML_Serializer</a>.  Make sure you have it installed and available in your PHP include path using PEAR.
   If you are not set up to use PEAR, you can use the copy included with this SDK's BubblePop sample, under <code>samples/BubblePop/php/pear</code>.  Just place a copy of this folder
    under your webroot and make sure it is in your PHP include path.
3. Include/require Splyt.php to get access to the Splyt class.  No other include/require statements are required to use Splyt.
4. In order to access Splyt functionality, you must first make a call to the init method:

        $splyt = Splyt::init(
            <your customer id>,     // (required) Customer ID from the Splyt team.  If you don't have one, contact them.
            <user id>               // (optional) Provide a user id if you have one.
            <device id>             // (optional) Provide a device id if you have one.  ID should always be the same for a particular installation of the app.
        );
    - Splyt::init must be called at least once per thread of execution (program, web service) and is the only proper way to get access to a Splyt instance.
    - Either a userId or deviceId must be provided.  You can also specify both.  If you are using the PHP SDK in conjunction with a client SDK, you should insure that you use consistent userId and deviceId across the SDKs.
    - Please see the API documents and the BubblePop sample for more information.
5.  Begin a session.  As early as possible in your application's initial flow, call begin() in the session plugin to mark the beginning of a period of activity.

        $splyt->Session->begin();

<h2>Instrumentation</h2>
Instrument your application using the additional plugins provided in the SDK.  Refer to the the sample application(s) for examples.

- Here are few best practices to keep in mind, many more can be found in comments of the Exhauster sample application.
    - If you do have both a user ID and a device ID, and you find yourself calling $splyt->Custom->updateUserState({...}), you should also call $splyt->Custom->updateDeviceState({}) with the same arguments.
    - You should apply all transaction properties to a transaction at the earliest moment that you can.  Do not defer to a call to $splyt->Custom->endTransaction() that which can be set in $splyt->Custom->beginTransaction() or $splyt->Custom->updateTransactionProgress(...).
    - You should always specify your user/device's state when they are available.  Failing to consistently specify one or both can lead to inconsistencies in the data.

<h2>Tuning & Testing</h2>
The Splyt tuning system drives A/Z testing and dynamic tuning.  It is easy to set up and use.

1. If you are utilizing the tuning system, you will want to cache the tuning variables for a given user and device.  It is recommended you do this as early as possible in the flow and block on the call so you can be sure that everything is ready to go. The following example shows one way to do this.

        <?php  // this may be a web service which is called from my client app near startup
        $splyt = Splyt::init(...);
        $splyt->cacheVariables();
        ... further initialization, which may rely on tuning variables
    - This technique relies on the creation of a Splyt_ext.php file, which should be placed in the same folder with Splyt.php.
    - Splyt_ext.php must contain a Splyt_ext class implementing store, retrieve, and clear methods.
    - The store, retrieve, and clear methods may use any technique available to store key/value pairs, such that they are consitently available for calls to cacheVariables and getVar.
    - The BubblePop example uses a client-specific cookie technique to accomplish this.  For php programs, or situations where all getVar calls occur in the same thread of execution as cacheVariables, a static array in memory could even be used.
2. Now you can retrieve a named tuning variable using Splyt::getVar.  Note that for each call to getVar, a default value must be provided, which is used in the event that no tuned value is found on the server.

        <?php
        $splyt = Splyt::init(...);
        $myVar = $splyt->getVar("myVariable", 10);
        $myOtherVar = $splyt->getVar("myOtherVariable", "someDefaultString");

<h2>Sample Applications</h2>

To run samples, simply copy the folder containing the sample under your webroot and launch the included index.html.

- BubblePop - This sample application is a simple [Shell Game] (http://en.wikipedia.org/wiki/Shell_game) that demonstrates basic integration of both data collection and tuning & testing.  It incorporates Splyt's session and purchasing plugins.

<!-- dirty trick to get doxygen to auto-copy this file for us -->
<div style="display: none;">
\image html SplytSDKLogoFooter.png
</div>