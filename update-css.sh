#!/bin/bash
colorScheme="$1"
baseDirectory=$(cd `dirname $0`; pwd)

if [ -z "${colorScheme}" ]
then
        echo "usage: $(basename $0) <color scheme>"
        echo "Where <color scheme> is the color scheme file (*.colors)"
        exit 1
fi

echo "Using the following colors scheme:"
cat ${colorScheme}

for file in $(ls -1 *.css.colors)
do
	cp ${file} ${file%.colors}
done

while read line
do

	name=$(echo ${line} | cut -f1 -d':' | tr -d '[:blank:]')
	color=$(echo ${line} | cut -f2 -d':' | tr -d '[:blank:]')

	for file in $(ls -1 *.css.colors)
	do
		sed -i "s|\[\[${name}\]\]|${color}|g" ${file%.colors}
	done	

done < ${colorScheme}

