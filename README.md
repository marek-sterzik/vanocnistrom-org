# www.vanocnistrom.org

This is the code for the web application of [www.vanocnistrom.org](https://www.vanocnistrom.org). Application allows to decorate a virtual christmas tree using a well-defined API.
Application may be used for educational purposes or just for fun.

## Development environment

The development environment is based on [spsostrov-php-runtime](https://github.com/marek-sterzik/spsostrov-php-runtime), where also details of the usage are documented. The
platform expects a working docker and Linux-based environment. Platform is easily usable inside of a linux distribution or even on Windows using the WSL subsystem (Windows
subsystem for Linux).

## Quick start

After installing docker and making the docker available for an ordinary user, just run the command:

```
bin/docker start
```

**All commands should be invoked using a regular user and never should be invoked as root!**

The application will be running using the port you have configured in the configuration section.

For example:
```
http://localhost:8080
```

## Licence

The applicaiton may be distributed under the MIT licence.
