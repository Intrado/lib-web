#!/bin/bash
# build.sh - every project has a thin bash script that can run a build,
# and produce a zip archive to be deployed.

project="lib-web"
echo "BUILD: $project"

# Command line arguments and their defaults
runTests=0
while getopts ":t" opt; do
	case $opt in
                t)
			runTests=1
			echo "Tests will execute after build"
			;;

		\?)
			echo "Invalid option: -$OPTARG" >&2
			echo
			echo "Usage: ./build.sh [-t]"
			echo
			echo "-t run tests after build, but before making the distribution ZIP"
			echo
			echo "FAIL! :-("
			exit 1
			;;
	esac
done

# Build it
echo "Copying to build tree..."
rsync -aq --delete --exclude=.gitignore src/ build/

# Test it
if [ $runTests -eq 1 ]; then
	echo "Testing..."
	./test.sh
	if [ $? -ne 0 ]; then
		echo "FAIL! :-("
		exit 1
	fi
	echo "Tests pass!"
fi

echo "Done! :-)"

