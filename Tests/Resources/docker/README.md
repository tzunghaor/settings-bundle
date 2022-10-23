These docker definitions are meant for the bundle developer to test the bundle against
different php versions locally.
Default php-cli docker image doesn't have zip extension enabled,
but it is needed for composer update, so creating custom docker images.