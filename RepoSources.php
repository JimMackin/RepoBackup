<?php
interface RepoSource{
  function setCredentials($params);
  function getRepositories();
}

class BitbucketSource implements RepoSource{

  function setCredentials($params){
    $this->user = strtolower($params['user']);
    $this->pass = $params['pass'];
    $this->client = new Bitbucket\API\Repositories();
    $this->client->setCredentials( new Bitbucket\API\Authentication\Basic($params['user'], $params['pass']));
  }

  function getRepositories(){
      $res = $this->client->all($this->user);
$list = json_decode($res->getContent(),true);
$repos = $list['values'];
while(!empty($list['next'])){
  $list = $this->client->getClient()->setApiVersion('2.0')->request($list['next']);
  $list = $list = json_decode($list->getContent(),true);
  $repos = array_merge($repos,$list['values']);
}
$ret = array();
foreach($repos as $repo){
  $url = $repo['links']['clone'][1]['href'];
  //$urlBits = parse_url($url);
  //$url = $urlBits['scheme']."://".$urlBits['user'].":".$this->pass."@".$urlBits['host'].$urlBits['path'];
  //print_r($url);
  //die();
  $ret[] = array('name'=>$repo['name'], 'clone_url' => $url,'type'=>$repo['scm']);
}

return $ret;
  }
}

class GitHubSource implements RepoSource{

  function setCredentials($params){
    $this->user = $params['user'];
    $this->client = new \Github\Client(
        new \Github\HttpClient\CachedHttpClient(array('cache_dir' => '/tmp/github-api-cache'))
    );
    $this->client->authenticate($params['token'], Github\Client::AUTH_HTTP_TOKEN);
  }
  function getRepositories(){
  $repos = $this->client->api('user')->repositories($this->user);
  $ret = array();
  foreach($repos as $repo){
    $ret[] = array('clone_url' => $repo['clone_url'],'name'=>$repo['name'],'type'=>'git');
  }

  $gists = $this->client->api('gists')->all();
  foreach($gists as $gist){
    $ret[] = array('clone_url' => $gist['git_pull_url'],'name'=>'gists/'.$gist['id'],'type'=>'git');
  }
  return $ret;
  }
}
