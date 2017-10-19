#!/bin/sh
rm composer.* .gitattributes .gitignore README.md plugins.sqlite blacklist.txt .travis.yml
rm -r tests data
git push origin master
