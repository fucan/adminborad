#!/bin/bash
#该脚本是自动获取两个版本之间文件的变化，并打包，不存在的文件会自动过滤
#注意:该脚本只能在git下有效且必须放在git仓库的根目录下
if [ "$1" == "" ] || [ "$2" == "" ] || [ "$1" == "$2" ]
then
echo "error,different hash1 hash2 ";
exit
fi

path=$(pwd)
#echo $path
time=$(date +%Y%m%d_%H_%M_%S)
updatename="different_"$time".tar.gz"
array=($(git diff $1 $2 --name-only))
#array=($(git log --pretty=format:"" --name-only --after="$1" ./))
declare -A filemap
for i in ${array[@]}
do
#文件是否存在
if [ -z "${filemap[$i]}" ] && [ -f "$i" ]
then
filemap[$i]=1
fi
done

for i in ${!filemap[@]}
do
data=$data$i"\n"
done
echo -e $data | xargs tar -czf $updatename
