# Github Repository Configurator

Configure multiple GitHub repositories via CLI commands.

### Installation

#### Composer

- `composer create-project mabar/github-repository-configurator path/to/project --stability dev`
- Generate your private Github token [here](https://github.com/settings/tokens/new). You will be asked for it during instalation.

## Usage

#### Replace default labels with more useful

```
<project>/bin/console configurator:replace-default-labels <owner> --repository <repository>
```

- If repository is not specified so all repositories are configured
- Currently not working description
- Added are
    - docs
    - need more info
- Renamed are
    - enhancement -> feature
- Removed are
    - good first issue
    - help wanted
    - invalid
    - wontfix
- Not modified are
    - bug
    - duplicate
    - question
- All not listed are ignored

#### Enable/disable wiki pages

```
<project>/bin/console configurator:wiki <enable|disable> <owner> --repository <repository>
```

- If repository is not specified so all repositories are configured

#### Enable/disable PR merge type

```
<project>/bin/console configurator:merge-type <enable|disable> <squash|rebase|merge> <owner> --repository <repository>
```

- If repository is not specified so all repositories are configured
- Currently not working, because GitHub api don't support it :(
