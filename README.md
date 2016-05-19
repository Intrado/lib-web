# lib-web - A library of code/resources used by multiple projects

## Requirements

The following additional external tools are used for building this application:

 * git - to check out code for external dependencies
 * rsync - for "smart" file copy operations
 * php (CLI) - for running the tests

## Testing

Run `test.sh` to test everything that can be tested. Currently test coverage includes:

 * PHP source lint for obvious syntax errors


## Building

Run `build.sh` to build everything needing to be assembled together into the build directory. Note that the build results in public and private directories with collections of files that are relavant for private use (includes, class definitions, etc) or public use (images, CSS files, etc. which must be delivered to a client). The calling application may selectively include either or both as needed to satisfy its requirements.


## Dependencies

None!


## Configuration

None! Any relevant configurations are application-specific so see the documentation for the application which includes this. Note that there may be lingering tendrils within this code base which refer to settings.ini, but the calling application must specify/satisfy this as needed - there is nothing to be done here for this library.

