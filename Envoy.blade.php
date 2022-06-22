@servers(['web' => 'emery@monitor.uac.bj'])

@setup
    $dir = "/home/emery/sysdpp";
    $release = $dir."/releases/".date('YmdHis');
    $shared = $dir."/shared";
    $repo = $dir."/repo";
    $currentRelease = $dir."/currentRelease";
    $releaseNumber = 3;
    $dirLinks = ["tmp/cache/models","tmp/cache/persistent","tmp/cache/views","tmp/sessions","tests","logs"];
    $fileLinks = ["config/app.php","config/database.php",".env"];
    $remote = false; 
@endsetup

@macro('deploy')
    createRelease
@endmacro

@task('prepare')
    mkdir -p {{ $shared }};
    @if($remote)
        git clone {{ $remote }} {{ $repo }}
    @else
        mkdir -p {{ $repo }};
        cd {{ $repo }};
        git init --bare;
    @endif
@endtask


@task('createRelease')
    mkdir -p {{ $release }};
    cd {{ $repo }};
    @if($remote)
        git remote update;
    @endif
    git archive main | tar -x -C {{ $release }};
    echo  "Release {{ $release }} created";
@endtask