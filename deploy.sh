#!/bin/bash
# Original of this script: https://github.com/thenbrent/multisite-user-management/blob/master/deploy.sh
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# main config
PLUGINSLUG="geoip-detect"
CURRENTDIR=`pwd`
MAINFILE="geoip-detect.php" # this should be the name of your main php file in the wordpress plugin

# git config
GITPATH="$CURRENTDIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/geoip-detect/" # Remote SVN repo on wordpress.org, with no trailing slash
SVNUSER="benjaminpick" # your svn username


# Let's begin...
echo ".........................................."
echo 
echo "Preparing to deploy wordpress plugin"
echo 
echo ".........................................."
echo 

# Check version in readme.txt is the same as plugin file
#NEWVERSION1=`grep "^Stable tag" $GITPATH/readme.txt | awk -F' ' '{print $3}'`
#echo "readme version: $NEWVERSION1"
#NEWVERSION2=`grep "^Version" $GITPATH/$MAINFILE | awk -F' ' '{print $2}'`
#echo "$MAINFILE version: $NEWVERSION2"
#NEWVERSION3=`grep "^define.*VERSION" $GITPATH/$MAINFILE | awk -F"'" '{print $4}'`
#echo "$MAINFILE define version: $NEWVERSION3"

# if [ "$NEWVERSION1" != "$NEWVERSION2" ] || [ "$NEWVERSION1" != "$NEWVERSION3" ]; then echo "Versions don't match. Exiting...."; exit 1; fi
#if [ "$NEWVERSION2" != "$NEWVERSION3" ]; then echo "Versions don't match. (php: '$NEWVERSION2', define: #'$NEWVERSION3') Exiting...."; exit 1; fi

echo "Versions match in readme.txt and PHP file. Let's proceed..."

#echo "Compressing JS files..."
#java -jar ~/bin/yuicompressor.jar --nomunge --preserve-semi -o "$GITPATH/tinymce/editor_plugin.js" $GITPATH/tinymce/editor_plugin_src.js
#java -jar ~/bin/yuicompressor.jar --nomunge --preserve-semi -o "$GITPATH/tinymce/wpcf-select-box.js" $GITPATH/tinymce/wpcf-select-box_src.js

cd $GITPATH
echo -e "Enter a commit message for this new version: \c"
read COMMITMSG
git commit -am "$COMMITMSG"

echo "Tagging new version in git"
git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

echo "Pushing latest commit to origin, with tags"
git push origin --all
git push origin master --tags

echo 
echo "Creating local copy of SVN repo ..."
svn co $SVNURL $SVNPATH

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/

echo "Ignoring github specific files, tests and deployment script"
svn propset svn:ignore "deploy.sh
README.md
.git
.gitignore
tests" "$SVNPATH/trunk/"

#if submodule exist, recursively check out their indexes (from benbalter)
if [ -f ".gitmodules" ]
then
echo "Exporting the HEAD of each submodule from git to the trunk of SVN"
git submodule init
git submodule update
git submodule foreach --recursive 'git checkout-index -a -f --prefix=$SVNPATH/trunk/$path/'
fi

echo "Changing directory to SVN and adding new files, if any"
cd $SVNPATH/trunk/
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
echo "Committing to trunk"
svn commit --username=$SVNUSER -m "$COMMITMSG"

echo "Creating new SVN tag & committing it"
cd $SVNPATH
svn copy trunk/ tags/$NEWVERSION1/
cd $SVNPATH/tags/$NEWVERSION1
svn commit --username=$SVNUSER -m "Tagging version $NEWVERSION1"

# echo "Removing temporary directory $SVNPATH"
# rm -fr $SVNPATH/

echo "*** FIN ***"
