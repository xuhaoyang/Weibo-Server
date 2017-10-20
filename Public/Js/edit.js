$(function () {



		$('form[name=editPwd]').validate({

		errorElement : 'span',
		success : function (label) {
			label.addClass('success');
		},
		//上部分验证完才会执行下面
		rules : {
			//AJAX提交
			old : {
				required : true,
			},
			new : {
				required : true,
			},
			newed : {
				required : true,
				equalTo	 : "#new"
			}
		},
		messages : {
			old : {
				required : ' ',
			},
			new : {
				required : ' '
			},
			newed : {
				required : ' ',
				equalTo  : '请确认两次密码一致'
			}
		}

	});




	//修改资料选项卡
	$('#sel-edit li').click( function () {
		var index = $(this).index();
		$(this).addClass('edit-cur').siblings().removeClass('edit-cur');
		$('.form').hide().eq(index).show();
	} );

	//城市联动
	var province = '';
	$.each(city, function (i, k) {
		province += '<option value="' + k.name + '" index="' + i + '">' + k.name + '</option>';
	});
	$('select[name=province]').append(province).change(function () {
		var option = '';
		if ($(this).val() == '') {
			option += '<option value="">请选择</option>';
		} else {
			var index = $(':selected', this).attr('index');
			var data = city[index].child;
			for (var i = 0; i < data.length; i++) {
				option += '<option value="' + data[i] + '">' + data[i] + '</option>';
			}
		}
		
		$('select[name=city]').html(option);
	});

	//星座默认选项
	$('select[name=night]').val(constellation);


	//所在地默认选项
	address = address.split(' ');

	//省份
	$('select[name=province]').val(address[0]);

	//each是jq的循环 用于城市联动
	$.each(city , function(i,k){
		if(k.name == address[0]){
			var str = '';
			for(var j in k.child){
				str += '<option value="' + k.child[j] + '" ';
				if(k.child[j] == address[1]) {
					str += 'selected="selected"';
				}
				str += '>' + k.child[j] + '</option>';
			}
			//具体城市
			$('select[name=city]').html(str);
		}

	});


	//头像上传Uploadify插件
	$('#face').uploadify({
		swf : PUBLIC + '/Uploadify/uploadify.swf',	//引入uploadify核心flash文件
		uploader : uploadUrl,	//PHP处理脚本地址
		width : 120 ,			//上传按钮宽度
		height : 30 ,			//上传按钮高度
		buttonImage : PUBLIC + '/Uploadify/browse-btn.png',	//上传按钮背景图地址
		fileTypeDesc : 'Image File',	//选择文件提示文字
		fileTypeExts : ' *.jpeg;*.jpg;*.png;*.gif ',	//允许选择的文件类型
		formData : {'session_id' : sid},
		//上传成功后的回调函数
		onUploadSuccess : function(file , data , response ) {
			eval('var data = ' + data);
			if(data.status){ 
				$('#face-img').attr('src' , ROOT + '/Uploads/Face/' + data.path.max);
				$('input[name=face180]').val(data.path.max);
				$('input[name=face80]').val(data.path.medium);
				$('input[name=face50').val(data.path.mini);
			} else {
				alert(data.msg);
			}
		}
	});
	
});