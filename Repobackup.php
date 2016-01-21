<?php

require_once 'vendor/autoload.php';

$repoBackup = new RepoBackup();
$repoBackup->run();

class RepoBackup{
  private $repositories = null;
  private $gists = null;
  private $gistsFolder = "gists/";
  private $repositoriesFolder = "repositories/";

  public function __construct(){
    $this->user = "JimMackin";
    $token = "Redacted";
    $this->client = getGitHubClient($token);
    $this->git = new PHPGit\Git();
    $this->cwd = getcwd()."/Backups/".$this->user."/";
  }
function run(){
  $this->backupRepositories();
  $this->backupGists();
}
  function backupRepositories(){
    /*if(!is_dir($this->repositoriesFolder)){
      mkdir($this->repositoriesFolder);
    }*/
    $this->repositories = $this->client->api('user')->repositories($this->user);
    foreach($this->repositories as $repository){
//print_r($repository);
echo "Pulling ".$repository['full_name']."\n";
$pullUrl = $repository['clone_url'];
$repoName = $repository['name'];
$this->backupRepo($pullUrl, $this->cwd."/".$this->repositoriesFolder.$repoName);
    }

  }
  function backupGists(){
    /*if(!is_dir($this->gistsFolder)){
      mkdir($this->gistsFolder);
    }*/
    $this->gists = $this->client->api('gists')->all();
    foreach($this->gists as $gist){
        $desc = $gist['description'];
       $pullUrl = $gist['git_pull_url'];
       $repoName = $gist['id'];
        //print_r($gist);
        echo "Pulling $desc\n";
        $this->backupRepo($pullUrl, $this->cwd."/".$this->gistsFolder.$repoName);
    }
  }
  function backupRepo($cloneURL, $folder){
    echo "Backup Repo: $cloneURL $folder\n";
    if(!file_exists($folder . DIRECTORY_SEPARATOR . '.git')){
      $this->git->clone($cloneURL, $folder);
   }
    $this->git->setRepository($folder);
    $this->git->fetch->all();
  }

}



function getGitHubClient($token){
  $client = new \Github\Client(
      new \Github\HttpClient\CachedHttpClient(array('cache_dir' => '/tmp/github-api-cache'))
  );
  $client->authenticate($token, Github\Client::AUTH_HTTP_TOKEN);
  return $client;
}
