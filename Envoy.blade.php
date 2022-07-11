@servers(['web' => 'emery@monitor.uac.bj'])

@setup
    $dir = "/home/emery/sysdpp";
    $release = $dir."/releases/".date('YmdHis');
    $shared = $dir."/shared";
    $repo = $dir."/repo";
    $currentRelease = $dir."/current";
    $releaseNumber = 3;
    $dirLinks = ["tmp/cache/models","tmp/cache/persistent","tmp/cache/views","tmp/sessions","tests","logs"];
    $fileLinks = ["config/app.php","config/database.php","config/.env"];
@endsetup

@macro('deploy')
    createRelease
    composer
    links
    generateKey
    currentRelease
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
    git archive master | tar -x -C {{ $release }};
    echo  "Release {{ $release }} created";
@endtask

@task('composer')
    mkdir -p {{ $shared }}/vendor;
    ln -s -f {{ $shared }}/vendor {{ $release }}/vendor;
    cd {{ $release }};
    composer update --no-dev --no-progress ;{{-- --ignore-platform-reqs --}}
@endtask

@task('links')
    cd {{ $dir }}
    touch {{ $release }}/{{ ".env" }}
    @foreach($dirLinks as $link)
        mkdir -p {{ $shared }}/{{ $link }};
        @if(strpos($link,'/'))
            mkdir -p {{ $release }}/{{ dirname($link) }};
        @endif
        chmod 777 {{ $shared }}/{{ $link }};
        ln -f -s {{ $shared }}/{{ $link }} {{ $release }}/{{ $link }}; 
    @endforeach

    @foreach($fileLinks as $link)
        ln -s -f {{ $dir }}/{{ $link }} {{ $release }}/{{ $link }}; 
    @endforeach
    echo "Link created";
@endtask

@task('rollback')
    rm -f {{ $currentRelease }};
    cd {{ $release }}
    ls {{ $dir }}/releases | tail -n 2 | head -n 1 | xargs -I{} -r ln -s -f releases/{} {{ $currentRelease }};
@endtask

@task('createMigration')
    cd {{ $release }};
    php artisan migrate --force;
@endtask

@task('generateKey')
    cd {{ $release }};
    php artisan key:generate ;
@endtask

@task('currentRelease')
    rm -f {{ $currentRelease }};
    ln -s -f {{ $release }} {{ $currentRelease }};
    ls {{ $dir }}/releases | sort -r | tail -n +{{ $releaseNumber+1 }} | xargs -I{} -r rm -rf {{ $dir }}/releases/{};
    chmod -R 777 {{ $release }}/storage/
    echo "Link {{ $currentRelease }} --> {{ $release }} created";
@endtask