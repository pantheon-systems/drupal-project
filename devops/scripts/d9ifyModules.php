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


try {

  if (!isset($_SERVER['OLD_SITE_NAME']) || !isset($_SERVER['NEW_SITE_NAME'])) {
    throw new Exception("You must set the environment variables OLD_SITE_NAME and NEW_SITE NAME. See /UPGRADING.md");
  }

  $newComposerFile = new SplFileObject(getcwd() . "/composer.json");
  $oldComposerFile = new SplFileObject(getcwd() . "/" . $_SERVER['OLD_SITE_NAME'] . "/composer.json");
  $newComposerOrigContents = $newComposerContents = json_decode($newComposerFile->valid() ? file_get_contents($newComposerFile->getRealPath()) : "{}", true, 512, JSON_THROW_ON_ERROR);
  $oldComposerContents = json_decode($oldComposerFile->valid() ? file_get_contents($oldComposerFile->getRealPath()) : "{}", true, 512, JSON_THROW_ON_ERROR);

  /**
   * STEP 1: Get composer requirements from old site if they exist, preferring the new composer values:
   */

  $newComposerContents['require'] = array_merge( $oldComposerContents['require'] ?? [], $newComposerContents['require'] ?? []);

  /**
   * STEP 2: Gather any stray modules currently not in the composer file
   */

  $regex = '/(\.info\.yml|\.info\.yaml?)/';
  $allFiles = iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($oldComposerFile->getRealPath()))));
  $max = count($allFiles);
  $current = 0;
  $infoFiles = array_filter($allFiles, function(SPLFileInfo $file) use ($regex, &$max, &$current) {
    progressBar($current++, $max);
    return preg_match($regex, $file->getFilename()) && !strpos($file->getFilename(), 'test');
  });
  $additionalContribs = [];
  foreach ($infoFiles as $fileName => $fileInfo) {
    $contents = file_get_contents($fileName);
    preg_match('/project\:\ ?\'(.*)\'$/m', $contents, $projectMatches);
    preg_match('/version\:\ ?\'(.*)\'$/m', $contents, $versionMatches);
    if (is_array($projectMatches) && isset($projectMatches[1])) {
      if ($projectMatches[1]) {
        $additionalContribs[ "drupal/" . $projectMatches[1] ] = "^" . str_replace("8.x-", "", $versionMatches[1]);
      }
    }
  }
  $newComposerContents['require'] = array_merge($additionalContribs, $newComposerContents['require']);
  $diffRequirements = array_diff($newComposerContents["require"], $newComposerOrigContents["require"]);

  echo PHP_EOL . PHP_EOL . "The following requirements were found from the previous site install:" . PHP_EOL;
  print_r($diffRequirements);
  echo PHP_EOL . PHP_EOL . "This does not include any custom themes or modules.";
  echo PHP_EOL . PHP_EOL;

  /**
   *  STEP 3: Write the file. Last chance to turn back before we start actually changing stuff.
   */

  echo "Write these changes to the composer file?  Type 'yes' to continue: ";
  $handle = fopen ("php://stdin","r");
  $line = fgets($handle);
  if(trim($line) != 'yes'){
    echo "ABORTING!\n";
    exit;
  }
  copy($newComposerFile->getRealPath(), dirname($newComposerFile->getRealPath())."/composer-backup-". uniqid() . ".json");
  file_put_contents($newComposerFile->getRealPath(), json_encode($newComposerContents, JSON_PRETTY_PRINT, 5));
} catch (\Exception $e) {
  echo $help_text . PHP_EOL;
  echo $e->getMessage();
  exit();
} catch (\Throwable $t) {
  echo $help_text . PHP_EOL;
  echo $t->getMessage();
  exit();
}


function progressBar($done, $total) {
    $perc = floor(($done / $total) * 100);
    $left = 100 - $perc;
    $write = sprintf("\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total", "", "");
    fwrite(STDERR, $write);
}



