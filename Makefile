all : css js

css :
	make -C ./less

js :
	make -C ./yui/src

.PHONY : css js

