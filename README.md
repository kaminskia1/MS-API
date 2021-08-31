# MS-API

### Abstract
This API was developed under contract for a now-defunct company. This source is provied as-is, with the only intention
being to help others who may find themselves in a similar circumstance - bridging the gap between managed software and a
database maintained by an Invision Power Suite (IPS) installation. This REST api was designed to be as lightweight as
possible, and does not utilize an underlying framework such as Symfony or Laravel but instead basic utility provided by
IPS. Authentication, Heartbeat, File-Serving, and Logging are just a handful of the provided 'mini' api's, of which can
be dynamically created / removed at the user's discresion. End-to-End encryption is supported. All traffic is routed
through HTTP/S.

### Requirements
    - PHP >= 7.3
    - IPS >= 4.0
    - CBPanel >= 1.3

### Installation
    1. Ensure that all requirements are met
    2. Clone and drop into desired directory
    3. Configure config.php and ensure production is set
    4. Use