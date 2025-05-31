# How to use the non-root Dockerfile.
I have rewritten the original Dockerfile for wavelog to use a non-root user for security/hardening in keeping with best practices of docker. I have done my best to make it as easy to use as is possible and hope that it helps others to maintain best security practices in their self hosted environments.

## Obtaining the non-root version.
For now, a fork of the original repository for [wavelog](https://github.com/wavelog/wavelog) that contains the non-root docker files will be maintained [here](https://github.com/John-WE1DER/wavelog). Should this be merged into the main branch of the original repo, I will update this file. For now, to obtain the non-root version, run `git clone https://github.com/John-WE1DER/wavelog.git` in your terminal to clone my forked repository.

## Dockerfile
No changes will need to be made in the Dockerfile itself as it uses ARG variables that will be set in the docker-compose file for the UID:GID depending on your local environment.

## docker-compose.yml
Looking into the docker-compose.yml file, there are some changes that will need to be made. All areas that need changing are commented and, keep in mind, this docker-compose.yml came from my environment so you will need to modify it to fit your needs.
### Set UID:GID args
In docker-compose.yml, look under services->wavelog->build->args and set the APP_UID and APP_GID to match your environment. You can obtain the uid/gid for the user you will use to run your container by running `id YOUR_NON_ROOT_DOCKER_USER` from your command line (assuming that you are running linux.) Output should be `uid=1000(username) gid=984(group) groups=984(group),150(anothergroup),998(yetanothergroup)` or similar. If you have setup a non-root user to run docker containers, both your UID and GID should be above 1000. Do not use a UID or GID below 1000 as these are generally system users/groups and the container may or may not work using such UID/GID. If not, setup a non-root user that is not your everyday user account. Take the UID and GID and plug them into the docker-compose.yml file in their respective locations; or, better even, create a `docker-compose.override.yml` file in the location that you cloned the repository to and copy/paste the code below to start it.
```
services:
  wavelog:
    build:
	  args:
	  	APP_UID: 1234 #Change this to reflect the UID from id command.
		APP_GID: 1234 #Change this to reflect the GID from id command.
```
### Set user key value
In docker-compose.yml, locate the key found at services->wavelog->user and replace UID:GID with the values from setting the UID:GID args above. I know this seems redundant, but it is necessary as the APP_UID and APP_GID values above are only used for variables located inside the Dockerfile. Setting user in the docker-compose.yml file tells docker what user to run the container as on the system.
## Other docker-compose.yml modifications.
Inside the docker-compose.yml file, you will find many other places that I have commented out items used in my environment. If you are familiar with docker, they are fairly self explanatory. I am not going to take the time to explain all of these as they are outside of the scope of this write up. If you get into a jam and would like help going through them, please reach out to [me](mailto:john@we1der.com) and I will do my best to help you.