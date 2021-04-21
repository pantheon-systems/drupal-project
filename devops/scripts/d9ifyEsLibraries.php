#!/usr/bin/env php -dmemory_limit=2048m
<?php

$help_text = <<<EOT

 *******************************************************************************
 * THIS SCRIPT IS IN ALPHA VERSION STATUS AND AT THIS POINT HAS VERY LITTLE    *
 * ERROR CHECKING. PLEASE USE AT YOUR OWN RISK.                                *
 *******************************************************************************
 * This script searches for every {modulename}.info.yml. If that file has a    *
 * "project" proerty (i.e. it's been thru the automated services at            *
 * drupal.org), it records that property and version number and ensures        *
 * those values are in the composer.json "require" array. Your old composer    *
 * file will re renamed backup-*-composer.json.                                *
 *******************************************************************************
 * The guide to use this file is in /UPGRADING.md                              *
 *******************************************************************************

EOT;


