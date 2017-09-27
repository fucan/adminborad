#!/bin/bash
#覆盖目录时，自动备份被覆盖的文件，备份文件在当前目录下
if [ "$1" == "" ] || [ "$2" == "" ] || [ "$1" == "$2" ]
then
echo "error,command like update src dst ";
exit
fi
src_dir=$1
dst_dir=$2

#自动备份
autoback()
{
mkdir $3
filelist=`ls $1`
for file in $filelist
do
	if [ -f $1$file ]
	then
		if [ -f $2$file ]
		then
			echo "autoback file to $3$file"
			cp $2$file $3$file
		fi
	else
		if [ -d $2$file ]
		then
			autoback $1$file"/" $2$file"/" $3$file"/"
		fi
	fi
done
}
back_dir="autoback_"$(date +%Y%m%d_%H_%M_%S)"/"
autoback $src_dir $dst_dir $back_dir
cp -R $src_dir"." $dst_dir
echo -e "\033[31mback up directory :$back_dir\033[0m"