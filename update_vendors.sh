#!/bin/sh

COMPONENTS='BrowserKit CssSelector EventDispatcher HttpFoundation Process ClassLoader DomCrawler Finder HttpKernel Routing'

cd vendor/Symfony/Component
for COMPONENT in $COMPONENTS
do
    cd $COMPONENT && git fetch origin && git reset --hard origin/master && cd ..
done
cd ../../..

cd vendor/doctrine-common
git fetch origin && git reset --hard origin/2.1.x
cd ../..

cd vendor/doctrine-dbal
git fetch origin && git reset --hard origin/2.1.x
cd ../..

cd vendor/monolog
git fetch origin && git reset --hard 1.0.2
cd ../..

cd vendor/pimple
git fetch origin && git reset --hard origin/master
cd ../..

cd vendor/twig
git fetch origin && git reset --hard v1.3.0
cd ../..
