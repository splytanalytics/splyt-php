#destroy and recreate the core calls ssf game...
rm -rf ~/rsb/ssf/bubblepopphp
mkdir -p ~/rsb/ssf/bubblepopphp/presentation

#copy all of BubblePop's source...
cp -R ../samples/BubblePop/* ~/rsb/ssf/bubblepopphp/presentation

#copy all of Splyt's source...
cp -R ../include/* ~/rsb/ssf/bubblepopphp/presentation/php/Splyt