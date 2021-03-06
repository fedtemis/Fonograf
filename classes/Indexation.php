<?php
use PhpId3\Id3TagsReader;
class Indexation {
  private static $filesToIndex = array();
  private static $indexedTracks = array();
  private static $url = _PLAYER_SERVER_.':'._PLAYER_PORT_;
  private static function indexationProgress($percentage) {
    $progressFile = 'download/reindexProgress.json';
    if ($percentage > 100 || $percentage < 0) {
      file_put_contents($progressFile,'');
    }
    else {
      $contents = array(
        'progress'=>(int)$percentage
      );
      file_put_contents($progressFile,json_encode($contents));
    }
  }
  public static function indexMusic() {
    Colors::rainbow();
    self::indexationProgress(0);
    self::recursiveIndexation(_MUSIC_FOLDER_);
    self::indexFilesByMeta();
    Colors::setColor('#FF6600');
    Colors::blinkLeds(0,0.4,3);
    self::putInDatabase();
    self::indexationProgress(100);
    Colors::setColor('#00FF00');
    Colors::blinkLeds(0,0.2,4);
  }

  private static function getFileMeta($filePath) {
    $post = array(
      'secret' => _PLAYER_SECRET_,
      'command' => 'getFileMeta',
      'value' => $filePath,
    );
    $http = Http::get(self::$url, $post);
    if ($http->status == 200) {
      return (array)json_decode($http->data);
    }
    return false;
  }

  private static function recursiveIndexation($folder) {
    foreach (glob($folder.'/*') as $item)
    {
      if (is_dir($item)) {
        self::recursiveIndexation($item);
      }
      else if (is_file($item)) {
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (in_array($ext, json_decode(_MUSIC_TYPES_))) {
          self::$filesToIndex[] = array('file'=>str_replace($folder.'/', '', $item), 'folder'=>$folder);
        }
      }
    }
  }

  private static function indexFilesByMeta() {
    $previousPercentage = 1.0;
    $percentage = 49.0;
    $filesCount = count(self::$filesToIndex);
    $countPercentage = $percentage/$filesCount;
    foreach (self::$filesToIndex as $key => $item) {
      if ($meta = self::getFileMeta($item['folder'].'/'.$item['file'])) {
        if ($meta != '') {
          $track = new Track();
          $track->name = Database::cleanInput($meta['title'], false);
          $track->album = Database::cleanInput($meta['album'],false);
          if ($meta['performer']!='')
            $track->artist = Database::cleanInput($meta['performer'],false);
          else
            $track->artist = Database::cleanInput($meta['artist'],false);
          $track->track = (int)explode('/',Database::cleanInput($meta['tracknumber'],false))[0];
          $track->genre = Database::cleanInput($meta['genre'],false);
          $track->year = (int)$meta['date'];
          $track->path = Database::cleanInput($item['folder']);
          $track->file = Database::cleanInput($item['file']);
          if ($track->name != '' && $track->artist != '') {
            self::$indexedTracks[] = $track;
          }
        }
      }
      self::indexationProgress((int)(($key+1)*$countPercentage)+$previousPercentage);
    }
  }

  private static function putInDatabase() {
    $previousPercentage = 50.0;
    $percentage = 49.0;
    $trackCount = count(self::$indexedTracks);
    $countPercentage = $percentage/$trackCount;

    Database::getInstance()->execute('TRUNCATE TABLE `tracks`;');
    Database::getInstance()->execute('TRUNCATE TABLE `artists`;');
    Database::getInstance()->execute('TRUNCATE TABLE `albums`;');

    foreach(self::$indexedTracks as $key => $track) {
      $artist = Database::getInstance()->getRow('SELECT * FROM `artists` WHERE `name`=\''.$track->artist.'\';');
      if ($artist == null) {
        Database::getInstance()->execute('INSERT INTO `artists`(`name`) VALUES(\''.$track->artist.'\');');
        $artist = Database::getInstance()->getRow('SELECT * FROM `artists` WHERE `name`=\''.$track->artist.'\';');
      }

      $pathHash = md5($track->path);

      $album = Database::getInstance()->getRow('SELECT * FROM `albums` WHERE `name`=\''.$track->album.'\' AND `pathHash`=\''.$pathHash.'\';');
      if ($album == null) {
        Database::getInstance()->execute('INSERT INTO `albums`(`name`,`pathHash`) VALUES(\''.$track->album.'\',\''.$pathHash.'\');');
        $album = Database::getInstance()->getRow('SELECT * FROM `albums` WHERE `name`=\''.$track->album.'\' AND `pathHash`=\''.$pathHash.'\';');
      }

      Database::getInstance()->execute('INSERT INTO `tracks`(`name`,`path`,`file`,`id_album`,`id_artist`,`track`,`genre`,`year`) VALUES(\''.$track->name.'\',\''.$track->path.'\',\''.$track->file.'\','.$album['id'].','.$artist['id'].','.$track->track.',\''.$track->genre.'\','.$track->year.');');
      self::indexationProgress((int)(($key+1)*$countPercentage)+$previousPercentage);
    }
  }
}
