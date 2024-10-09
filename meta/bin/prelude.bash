# This file should be sourced, not run
[[ -n $TRACE ]] && [[ $TRACE != 0 ]] && set -x

set -o errexit

cd $(dirname $0)/../..
base=$(pwd)

function warn {
    echo "$@" >&2
}

function die() {
    warn "$@"
    exit 1
}

function RUN() {
  [[ -n $DRY_RUN ]] && [[ $DRY_RUN != 0 ]] && _run=echo
  $_run "$@"
}

# for janky filesystems like WSL bind mounts
function rm_me_harder() {
    for dir in $*
    do
        dir=$1
        rm -rf $dir

        if [[ -d $dir ]]; then
            warn "Warning: $dir still exists after rm -rf.  Trying again in 5s"
            sleep 5
            rm -r $dir   # skip -f flag and just let it bomb if it fails
        fi
    done
}
