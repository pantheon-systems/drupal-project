#!/bin/bash
# This script is pretty tailored to assuming it's running in the CircleCI environment / a fresh git clone.
# It mirrors most commits from `pantheon-systems/drupal-project:release` to `pantheon-upstreams/drupal-project`.

set -xeuo pipefail

. devops/scripts/commit-type.sh

git remote add public $UPSTREAM_REPO_REMOTE_URL
git fetch public
git checkout release

# List commits between release-pointer and HEAD, in reverse
newcommits=$(git log release-pointer..HEAD --reverse --pretty=format:"%h")
commits=()

# Identify commits that should be released
for commit in $newcommits; do
  commit_type=$(identify_commit_type $commit)
  if [[ $commit_type == "normal" ]] ; then
    commits+=($commit)
  fi

  if [[ $commit_type == "mixed" ]] ; then
    2>&1 echo "Commit ${commit} contains both release and nonrelease changes. Cannot proceed."
    exit 1
  fi
done

# If nothing found to release, bail without doing anything.
if [[ -z "$commits" ]] ; then
  echo "No new commits found to release"
  echo "https://i.kym-cdn.com/photos/images/newsfeed/001/240/075/90f.png"
  exit 1
fi

# Cherry-pick commits not modifying circle config onto the release branch
git checkout -b public --track public/master
git pull
for commit in $commits; do
  if [[ -z "$commit" ]] ; then
    continue
  fi
  git cherry-pick "$commit" 2>&1
  git commit --amend --no-edit --author='Pantheon Automation <bot@getpantheon.com>'
done

# update the release-pointer
git tag -f -m 'Last commit set on upstream repo' release-pointer "$commit"

# Push released commits to a few branches on the upstream repo.
# Since all commits to this repo are automated, it shouldn't hurt to put them on both branch names.
release_branches=('master' 'main')
for branch in "${release_branches[@]}"; do
  git push public public:$branch
done

# Push release-pointer
git push -f origin release-pointer
