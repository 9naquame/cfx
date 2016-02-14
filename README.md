CFX
=================
The CFX framework, (forked from the WYF Framework) is a framework that has been 
put together over the past couple of years. Although it was built for a particular
project, it is quite general enough to apply to other projects.
 
I find it old and "cranky" .... but you'll definately find a time where it would 
be quite useful to you. It's not a one size fits all kind of framework. 
Its main aim is to help build those database driven apps where you
have views, lots of complex forms and reports. In actual sense it's a partially
built application and all you do is to code your modules in. 
  
Basic Architecture
------------------
The CFX Application Framework is somehow Object Oriented and it exhibits some 
basic model-view-controller (MVC) characteristics.
 
The framework provides API's which aid in:
 -  Interfacing with the PostgreSQL database
 -  Object Relational Mapping of Database Tables
 -  Form generation and validation.
 -  Views or lists generation and manipulation.
 -  Report Generation
 -  Testing through the PHPUnit test automation framework.
 -  User Access control and authentication.
 -  Logging and audit trails
 -  Any other things I forgot

Some limitations
----------------
The following things have been the pain of many developers who have worked with 
this framework:
 -  It only works with postgresql (for now)
 -  It hurts to theme your application (you can however mess with the css that 
    ships with the framework)
 -  You may have to write classes with long names like SystemSetupUsersRolesController ... smh

