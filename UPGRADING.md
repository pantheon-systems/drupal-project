# Upgrade to d9:

##  Is this for me?

Let me assure you that you do exist and have value. However, this guide may not be useful to you
unless you meet the following criterion.

1.  Do you have a Drupal 8 site hosted at Pantheon?

1.  Is your site a member of a pantheon "organization"? Not a dealbreaker either way, but you need to know.

    ```terminus site:info ${OLD_SITE_NAME} --format=json | jq -r ".organization"```

1.  If so, do you have the permissions to create new sites inside that org? If the answer is "No", you need that
    permission to use this guide.

1.  Do you have administrative permissions in Pantheon to move things like DNS names and primary site URL's?
    You can create the upgrade and get it working, but you will not be able to make the site live without permission
    to change those settings.


## Installed Tools:

1.  Install a standard set of open source command line tools:

    a. Apple Command Line Tools (```xcode-select --install```) + (homebrew)[https://brew.sh]

    OR

    b. (Windows Subsystem For Linux)[https://docs.microsoft.com/en-us/windows/wsl/install-win10]

1. install the command line utilities:  jq terminus direnv

   // TODO: commands to install for win/mac

## Step 1:

1. Authenticate terminus:

  - ```terminus auth:login --email={YOUR PANTHEON USER EMAIL ADDRESS}```

All the instructions will be based on this login information and user space.
If you stop or pause in the middle, you may have to login again. Terminus logins
expire about every 24 hours.


## PREP():

We're going to export our current site's pantheon site name. Terminus will use this site name
act on your behalf in the pantheon dashboard.

```export OLD_SITE_NAME=d8-test-site```

Then run the other lines to build names based on the old site's name.

```
export NEW_SITE_NAME=${OLD_SITE_NAME}-$(date +%Y)
export OLD_SITE_LABEL=$(terminus site:info ${OLD_SITE_NAME} --format=json | jq -r ".label")
export NEW_SITE_LABEL="${OLD_SITE_LABEL} $(date +%Y)"

// TODO: try these next commands for sites with and without an org
// ${ORG_COMMAND_SWITCH} should be empty if your site is not a member of an org
// and --org=ORG_ID if your site is a member of an org so adding it to any site create command
// will produce the desiered result.

export ORGANIZATION=$(terminus site:info ${OLD_SITE_NAME} --format=json | jq -r ".organization")
export ORG_COMMAND_SWITCH=$([[ ! -z "${ORGANIZATION}" ]] && echo "--org=${ORGANIZATION}" || "")

```

## main()

1.  Create a new sandbox on pantheon where the new site will reside

    - ```terminus site:create ${NEW_SITE_NAME} "${NEW_SITE_LABEL}" drupal9 ${ORG_COMMAND_SWITCH}```

1.  Clone new site and `cd` to the dir.

    - ```$(`terminus connection:info ${NEW_SITE_NAME}.dev --format=json | jq -r ".git_command"`)```

    - ```cd ${NEW_SITE_NAME}```

1.  Clone the Old site:

    ```$(`terminus connection:info ${OLD_SITE_NAME}.dev --format=json | jq -r ".git_command"`)```

1.  Make sure that git ignores the old site's codebase:

    ```echo "/${OLD_SITE_NAME}" >> .gitignore```
    ```git add .gitignore && git commit -m 'adding old sites codebase to gitignore'```

1.  Gather Ye Modules While Ye May

    This script will scan your old site folder for any contrib modules and make sure they're
    in the requirements list in the new site composer.

    ```devops/scripts/d9ifyModules.php```

1.  Copy custom themes and modules from old site:

    ```devops/scripts/d9ifyCustomizations.php```

1.  ESScript (Javascript) libraries

    We first need to differentiate between a few different types of ESScript requirements:

    1. ESScript Library requirements of the drupal CMS and installed modules

       Previous to d9, javascript libraries that were needed by the CMS and/or installed modules
       were installed in the `/libraries` folder inside your Drupal install. Composer 2 is able to
       manage those now, and we will look for the ones in your previous install and try to make sure
       they're in the composer.json.

       ```devops/scripts/d9ifyEsLibraries.php```

    1. ESScript dependencies of pre-processed and/or packaged feature scripts.

       React, Vue.js, Angular and other front end libraries may be used as well as SCSS compilers
       and image pre-processors. Currently, these are out-of-scope of this document, but Pantheon
       should have a product offering in late 2021 to start to address these development patterns.

1.  TODO: COPY CONFIGS

1.  TODO: Move exported content or move db?

1.  TODO: Terminus plan:move ${OLD_SITE_NAME} => ${NEW_SITE_NAME}

1.  TODO: Terminus dns:move ${OLD_SITE_NAME} => ${NEW_SITE_NAME}


## POST()
