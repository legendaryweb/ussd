# ussd
A simple ussd app in PHP

A USSD simply speaking is an app that is accessed by a user via a mobile device by dialing.
usually including stars and ending with a hash <br />e.g. \*150\*321# (Not accociated to this APP)

By typing in the USSD code you submit a request to the USSD server

The USSD server will access the USSD Client URL, process the data received and format to
display on the mobile phone.
This is an example of a simple app found at such a client URL.

Take note this USSD app expects certain $_GET values from the USSD server. So it will need to be amended if you plan to use it on another server.
