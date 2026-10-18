<?php
$objStories = $this->getObject('sitestories', 'stories');
$allStories =  $objStories->fetchPreLoginCategory('prelogin');

echo $this->getObject('contextualhelp','help')->show('stories','reading');
echo $allStories;
?>