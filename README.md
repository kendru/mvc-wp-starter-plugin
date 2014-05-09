MVC Starter Wordpress Plugin
============================

This starter plugin brings an MVC framework to WordPress for rapid plugin
development. This project was born out of frustration with trying to build
large plugins on the WordPress plugin architecture. It includes a light
framework for building plugins in an MVC style. The `sample` directory includes
a basic plugin using the framework.

## Requirements
+ PHP >= 5.3
+ WordPress >= 3.0

## Components

## Front Controller (Applciation.php)
Initializes the application. Performs bootstrap logic, registers initial plugin
hooks, and routes request to the appropriate controller. 

## Controllers
Gathers all data needed to display, and renders a view. The rendered view is
not conceptually its own object, but it is an HTML template into which the view
is able to inject data.

### Router
Creates a Command object, which represents the request to be resolved, and
loads the correct Controller.

## TODOs
+ Implement a model framework
+ Re-organize directory/namespace structure to make it easier to work with as
a library
+ Replace Command objects with Request objects that contain more request data
and are used by the router itself to initialize the correct controller.
+ Upgrade to work with the latest version of WordPress
+ Add unit tests

## Contributing
This project is very young and has not had much development recently. If you
would like to see a feature added, please open an issue or submit a pull
request. The code is also in some severe need of unit tests, so any tests would
be tremendously helpful.

I started this project as a way to learn more about both WordPress and design
patterns in PHP. Thus, most of the code is in a very "rough draft" state and in
a state of design flux. It really needs to be re-architected to have a cleaner
separation or responsibilities and flow of control.

