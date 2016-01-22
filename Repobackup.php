<?php

require_once 'vendor/autoload.php';
require_once 'RepoSources.php';
$repoBackup = new RepoBackup();
$repoBackup->run();

class RepoBackup{
  private $repositories = null;
  private $gists = null;
  private $gistsFolder = "gists/";
  private $repositoriesFolder = "repositories/";

  public function __construct(){
    $this->git = new PHPGit\Git();
    $this->cwd = getcwd()."/Backups/";
  }

  function getInput($message){
    echo $message;
    return trim(fgets(STDIN));
  }
function run(){
  echo "Backing up\n";

  $line = $this->getInput("Backup GitHub [y/n]:");
  if(strtolower($line) == 'y'){
    $gitSource = new GitHubSource();
    $user = $this->getInput("GitHub Username:");
    $token = $this->getInput("GitHub API Token:");
    $gitSource->setCredentials(array('user'=>$user,'token'=>$token));
    $this->backupRepositories($gitSource,'GitHub/'.$user."/");
  }
  $line = $this->getInput("Backup BitBucket [y/n]:");
  if(strtolower($line) == 'y'){
    $user = $this->getInput("BitBucket Username:");
    $pass = $this->getInput("BitBucket Pass:");
    $bbSource = new BitbucketSource();
    $bbSource->setCredentials(array('user'=>$user,'pass'=>$pass));
    $this->backupRepositories($bbSource,'BitBucket/'.$user.'/');
  }
}
  function backupRepositories(RepoSource $src, $path){
    $repositories = $src->getRepositories();
    foreach($repositories as $repository){
      echo "Pulling ".$repository['name']."\n";
      $this->backupRepo($repository,$path);
    }

  }

  function backupRepo($repository,$path){
    $repoName = $repository['name'];
    $cloneURL = $repository['clone_url'];
    $folder = $this->cwd."/".$path.$repoName;
    echo "Backup Repo: $cloneURL $folder\n";
    if($repository['type'] == 'git'){
      if(!file_exists($folder . DIRECTORY_SEPARATOR . '.git')){
        echo "Calling $cloneURL\n";
         $this->git->clone($cloneURL, $folder);
      }
       $this->git->setRepository($folder);
       $this->git->fetch->all();
    }elseif($repository['type'] == 'hg'){
      $output = array();
      $return = 0;
      if(!file_exists($folder . DIRECTORY_SEPARATOR . '.hg')){
        echo "Calling "."hg clone ".$cloneURL. " ".$folder."\n";
        exec ("hg clone ".$cloneURL. " ".$folder, $output , $return);
      }
      echo "Calling "."hg pull -R ".$folder."\n";
      exec ("hg pull -R ".$folder, $output , $return);
    }else{
      echo "Unrecognised VC type!\n";
    }

  }

}
