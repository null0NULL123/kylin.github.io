var t = 60;    
function showTime(item){

	item ? item : item = '#btnGetCode';

	t -= 1;  
	$(item).text('重新发送（'+t+'）');   
	$(item).attr('disabled',"disabled");
	var f = setTimeout("showTime('"+item+"')",1000); 

	if(t==0){  
	   $(item).text('重新发送');
	   $(item).removeAttr("disabled");
	   window.clearTimeout(f);
	   t=60;
	}
}

jQuery(function($){
	
	$('.xintheme-loadmore').click(function(){

		//AJAX翻页
		var loadmore_data = {
			'action': 'loadmore',
			'query': xintheme.query,
			'paged' : xintheme.current_page
		};

		var button = $(this);
		
		$.ajax({
			url : xintheme.ajaxurl, // AJAX处理程序
			data : loadmore_data,
			type : 'POST',
			beforeSend : function ( xhr ) {
				button.html('<i class="iconfont icon-shuaxin" style="vertical-align: sub;"></i> 加载中...'); // 更改按钮文本，还可以添加预加载图像
			},
			success : function( data ){
				if( data ) { 
					button.html('<i class="iconfont icon-shuaxin" style="vertical-align: sub;"></i> 加载更多').parent().before(data); // 插入新的文章
					xintheme.current_page++;
 
					if (xintheme.current_page >= xintheme.max_page ) 
						button.parent().remove(); // 如果最后一页，删除按钮
 
					// 如果你使用一个需要它的插件，你也可以在这里触发“后加载”事件。
					// $( document.body ).trigger( 'post-load' );
				} else {
					button.parent().remove(); // 如果没有数据，也删除按钮
				}
			}

		});
	});

	if(xintheme.paging_type == 4){
		//AJAX翻页
		var canBeLoaded = true, // 此PARAM只允许在必要时启动Ajax调用
			bottomOffset = 2000; // 当你想加载更多的文章时，页面底部的距离（px）
	 
		$(window).scroll(function(){

		var loadmore_data = {
			'action': 'loadmore',
			'query': xintheme.query,
			'paged' : xintheme.current_page
		};

			if( $(document).scrollTop() > ( $(document).height() - bottomOffset ) && canBeLoaded == true ){
				$.ajax({
					url : xintheme.ajaxurl,
					data : loadmore_data,
					type :'POST',
					beforeSend: function( xhr ){
						// 您也可以在这里添加自己的预加载程序
						// Ajax调用正在进行中，我们不应该再运行它，直到完成
						canBeLoaded = false; 
					},
					success:function(data){
						if( data ) {
							$('.posts-wrapper').find('article:last-of-type').after( data ); // 在哪个位置插入文章
							canBeLoaded = true; // AJAX已经完成，现在我们可以再次运行它了
							xintheme.current_page++;
						}
					}
				});
			}
		});
	}





/*


	$('.collect-btn').click(function() {
		var _this = $(this);
		var pid = Number(_this.attr('pid'));
		var collect = Number(_this.children("span").text());
		if (_this.attr('uid') && !_this.hasClass('collect-yes')) {
			var uid = Number(_this.attr('uid'));
			$.ajax({
				type: 'POST',
				xhrFields: {
					withCredentials: true
				},
				dataType: 'html',
				url: xintheme2.ajax_url,
				data: 'action=collect&uid=' + uid + '&pid=' + pid + '&act=add',
				cache: false,
				success: function(response) {
					if (response != 0) _this.children("span").text(collect + 1);
					_this.removeClass("collect-no").addClass("collect-yes").attr("title", "已收藏");
					_this.children('i').attr('class', 'iconfont icon-collection_fill');
				}
			});
			return false;
		} else if (_this.attr('uid') && _this.hasClass('collect-yes') && _this.hasClass('remove-collect')) {
			var uid = Number(_this.attr('uid'));
			$.ajax({
				type: 'POST',
				xhrFields: {
					withCredentials: true
				},
				dataType: 'html',
				url: xintheme2.ajax_url,
				data: 'action=collect&uid=' + uid + '&pid=' + pid + '&act=remove',
				cache: false,
				success: function(response) {
					if (response != 0) _this.children("span").text(collect - 1);
					_this.removeClass("collect-yes").addClass("collect-no").attr("title", "点击收藏");
					_this.children('i').attr('class', 'iconfont icon-collection');
				}
			});
			return false;
		} else {
			return;
		}
	});

*/



	//投稿
	$(document).on('click', '.publish_post', function(event) {
	    event.preventDefault();
	    var title = $.trim($('#post_title').val());
	    var status = $(this).data('status');
	    $('#post_status').val(status);
	    var editor = tinymce.get('editor'); 
	    var content = editor.getContent();
	    $('#editor').val(content);

	    if( !content ){
	        alert('请填写文章内容！');
	        return false;
	    }

	    if( title == 0 ){
	        alert('请输入文章标题！');
	        return false;
	    }

	    $.ajax({
	        url: xintheme.ajaxurl,
	        type: 'POST',
	        dataType: 'json',
	        data: $('#post_form').serializeArray(),
	    }).done(function( data ) {
	        if( data.state == 200 ){
	            alert(data.tips);
	            window.location.href = data.url;
	        }else{
	            alert(data.tips);
	        }
	    }).fail(function() {
	        alert('出现异常，请稍候再试！');
	    });
	    
	});
	$(document).on('click', '.select-img', function(event) {
		event.preventDefault();
		
		var upload_img;  
	    if( upload_img ){   
	        upload_img.open();   
	        return;   
	    }   
	    upload_img = wp.media({   
	        title: '选择图片',   
	        button: {   
	        text: '添加为封面图',   
	    },   
	        multiple: false   
	    });   
	    upload_img.on('select',function(){   
	        thumbnail_img = upload_img.state().get('selection').first().toJSON(); 
	        if( thumbnail_img.subtype == 'png' || thumbnail_img.subtype == 'jpeg' || thumbnail_img.subtype == 'gif' ){
	            $('img.thumbnail').attr('src', thumbnail_img.url ).show();
	            $('input.thumbnail').val(thumbnail_img.id);
	        }else{
	            alert('请选择图片！'); 
	        }  
	    });   
	    upload_img.open(); 
	});



	//Pro版本 前端登录
	$('#login_form').submit(function(event) {
	    event.preventDefault();

		if(xintheme.close_vercode == 1){
			var login_name = $('#login_name').val().replace(/\s+/g,""),
			    password   = $('#password').val().replace(/\s+/g,""),
			    err        = 0;
		}else{
			var login_name = $('#login_name').val().replace(/\s+/g,""),
			    password   = $('#password').val().replace(/\s+/g,""),
			    vercode    = $('#vercode').val().replace(/\s+/g,""),
			    err        = 0;
		}

	    if( login_name ){
	        $('.login_name').removeClass('error').addClass('success');
	        //$('.login_name .lp-trps i').removeClass().addClass('iconfont icon-duihao');
	        //$('.login_name .lp-trps span').text('已输入');
	    }else{
	        $('.login_name').removeClass('success').addClass('error');
	        $('.login_name .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	        $('.login_name .lp-trps span').text('请输入用户名或E-mail');
	        err = 1;
	    }

	    if( password ){
	        $('.password').removeClass('error').addClass('success');
	        //$('.password .lp-trps i').removeClass().addClass('iconfont icon-duihao');
	        //$('.password .lp-trps span').text('已输入');
	    }else{
	        $('.password').removeClass('success').addClass('error');
	        $('.password .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	        $('.password .lp-trps span').text('请输入密码');
	        err = 1;
	    }

		if(xintheme.close_vercode != 1){
		    if( vercode ){
		        $('.vercode').removeClass('error').addClass('success');
		        //$('.vercode .lp-trps i').removeClass().addClass('iconfont icon-duihao');
		        //$('.vercode .lp-trps span').text('已输入');
		    }else{
		        $('.vercode').removeClass('success').addClass('error');
		        $('.vercode .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
		        $('.vercode .lp-trps span').text('请输入验证码');
		        err = 1;
		    }
	    }

	    if( err != 1 ){
	        $.ajax({
	            url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: $('#login_form').serializeArray(),
	        })
	        .done(function( data ) {
	            if( data != 0 ){
	                if( data.state == 200 ){
	                    $('.d-tips').removeClass('error').addClass('success').text(data.tips); 
	                    if( data.url ){
	                        window.location.href = data.url;
	                    }
	                }else if( data.state == 201 ){
	                    $('.d-tips').removeClass('success').addClass('error').text(data.tips); 
	                }
	            }else{
	                $('.d-tips').removeClass('success').addClass('error').text('请求错误！');   
	            }
	        })
	        .fail(function() {
	            alert('网络错误！');
	        });
	    }

	});

	//Pro版本 前端注册
	$('#register_form').submit(function(event) {
	    event.preventDefault();
	    var email_test = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;

		if(xintheme.close_vercode == 1){
		    var email	  = $('#email').val().replace(/\s+/g,""),
		    	pwd1      = $('#password_1').val().replace(/\s+/g,""),
		    	pwd2      = $('#password_2').val().replace(/\s+/g,""),
		    	err       = 0;
		}else{
		    var email	  = $('#email').val().replace(/\s+/g,""),
		    	pwd1      = $('#password_1').val().replace(/\s+/g,""),
		    	pwd2      = $('#password_2').val().replace(/\s+/g,""),
		    	vercode   = $('#vercode').val().replace(/\s+/g,""),
		    	err       = 0;
		}

	    if( email && email_test.test(email) ){
	        $('.email').removeClass('error').addClass('success');
	        $('.email .lp-trps i').removeClass().addClass('iconfont icon-duihao');
	        $('.email .lp-trps span').text('输入正确');
	    }else{
	        $('.email').removeClass('success').addClass('error');
	        $('.email .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	        if( email ){
	            $('.email .lp-trps span').text('输入错误');
	        }else{
	            $('.email .lp-trps span').text('请输入E-mail');
	        }
	        err = 1;
	    }

	    if( pwd1 && pwd2 ){

	        if( pwd1 === pwd2 ){
	            $('.password_1,.password_2').removeClass('error').addClass('success');
	            $('.password_1 .lp-trps i,.password_2 .lp-trps i').removeClass().addClass('iconfont icon-duihao');
	            $('.password_1 .lp-trps span,.password_2 .lp-trps span').text('输入正确');
	        }else{
	            $('.password_1,.password_2').removeClass('success').addClass('error');
	            $('.password_1 .lp-trps i,.password_2 .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	            $('.password_1 .lp-trps span,.password_2 .lp-trps span').text('密码不一致');
	            err = 1;
	        }

	    }else{
	        if( !pwd1 ){
	            $('.password_1').removeClass('success').addClass('error');
	            $('.password_1 .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	            $('.password_1 .lp-trps span').text('请输入密码');
	        }
	        if( !pwd2 ){
	            $('.password_2').removeClass('success').addClass('error');
	            $('.password_2 .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	            $('.password_2 .lp-trps span').text('请重复输入密码');
	        }
	        err = 1;
	    }

	    if(xintheme.close_vercode != 1){
		    if( vercode == '' ){
		        $('.vercode').removeClass('success').addClass('error');
		        $('.vercode .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
		        $('.vercode .lp-trps span').text('请输入验证码');
		        err = 1;
		    }else{
		        $('.vercode').removeClass('error').addClass('success');
		        $('.vercode .lp-trps i').removeClass().addClass('iconfont icon-duihao');
		        $('.vercode .lp-trps span').text('已输入');
		    }
	    }

	    if( err != 1 ){

	        $.ajax({
	            url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: $('#register_form').serializeArray(),
	        })
	        .done(function( data ) {
	            if( data != 0 ){
	                if( data.state == 200 ){
	                    $('.d-tips').removeClass('error').addClass('success').text(data.tips); 
	                    if( data.url ){
	                        window.location.href = data.url;
	                    }
	                }else if( data.state == 201 ){
	                    $('.d-tips').removeClass('success').addClass('error').text(data.tips); 
	                }
	            }else{
	                $('.d-tips').removeClass('success').addClass('error').text('请求错误！');   
	            }
	        })
	        .fail(function() {
	            alert('网络错误！');
	        });
	        

	    }

	});

	//注册邮箱验证
	function is_check_mail(str) {
		return /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/.test(str)
	}
	$('.captcha-clk').bind('click',function(){
		if( !is_check_mail($("#email").val()) ){
	        $('#captcha_inline').removeClass('success').addClass('error');
	        $('#captcha_inline .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	        $('#captcha_inline .lp-trps span').text('邮箱格式错误');
			return
		}
		
		var captcha = $(this);
		if(captcha.hasClass("disabled")){
	        $('#captcha_inline').removeClass('success').addClass('error');
	        $('#captcha_inline .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	        $('#captcha_inline .lp-trps span').text('您操作太快了，等等吧');
		}else{
			captcha.addClass("disabled");
			captcha.html("发送中...");
			$.post(
				_WPJAM_XinTheme.uri+'/user/action/captcha.php?'+Math.random(),
				{
					action: "WPJAM_XinTheme_captcha",
					email:$("#email").val()
				},
				function (data) {
					if($.trim(data) == "1"){
				        $('#captcha_inline').removeClass('error').addClass('success');
				        $('#captcha_inline .lp-trps i').removeClass().addClass('iconfont icon-duihao');
				        $('#captcha_inline .lp-trps span').text('已发送验证码至邮箱，也可能会出现在垃圾箱里哦~');
						var countdown=60; 
						settime()
						function settime() { 
							if (countdown == 0) { 
								captcha.removeClass("disabled");   
								captcha.html("发送验证码");
								countdown = 60; 
								return;
							} else { 
								captcha.addClass("disabled");
								captcha.html("重新发送(" + countdown + ")"); 
								countdown--; 
							} 
							setTimeout(function() { settime() },1000) 
						}

					}else if($.trim(data) == "2"){
				        $('#captcha_inline').removeClass('success').addClass('error');
				        $('#captcha_inline .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
				        $('#captcha_inline .lp-trps span').text('邮箱已存在');
						captcha.html("发送验证码");
						captcha.removeClass("disabled"); 
					}else{
				        $('#captcha_inline').removeClass('success').addClass('error');
				        $('#captcha_inline .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
				        $('#captcha_inline .lp-trps span').text('验证码发送失败，请稍后重试');
						captcha.html("发送验证码");
						captcha.removeClass("disabled"); 
					}
				}
			);
		}
	});


	//找回密码
	$(document).on('click', '.get_pwd-1', function(event) {
	    event.preventDefault();
	    
	    var email = $('#email').val().replace(/\s+/g,"");
	    var err = 0;
	    if( email ){
	        $('.email').removeClass('error').addClass('success');
	        $('.email .lp-trps i').removeClass().addClass('iconfont icon-duihao');
	        $('.email .lp-trps span').text('已输入');
	    }else{
	        $('.email').removeClass('success').addClass('error');
	        $('.email .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	        $('.email .lp-trps span').text('请输入用户名或E-mail');
	        err = 1;
	    }

	    if( err == 0 ){

	        $.ajax({
	            url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: {action: 'get_email_vcode',login_name: email},
	        })
	        .done(function( data ) {
	            if( data.state == 200 ){
	                $('.login-trps').removeClass('error').addClass('success').text(data.tips);
	                window.location.href = window.location.href;
	            }else{
	                $('.login-trps').removeClass('success').addClass('error').text(data.tips);
	            }
	        })
	        .fail(function() {
	            alert('网络错误，请稍候再试！');
	        });
	        

	    }

	});

	$(document).on('click', '.get_pwd-2', function(event) {
	    event.preventDefault();
	    
	    var vcode = $('#vcode').val().replace(/\s+/g,"");

	    if( vcode ){

	        $.ajax({
	            url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: {action: 'get_email_ver',vcode: vcode},
	        })
	        .done(function( data ) {
	            if( data.state == 200 ){
	                $('.login-trps').removeClass('error').addClass('success').text(data.tips);
	                window.location.href = window.location.href;
	            }else{
	                $('.login-trps').removeClass('success').addClass('error').text(data.tips);
	            }
	        })
	        .fail(function() {
	            alert('网络错误，请稍候再试！');
	        });

	    }else{
	        $('.login-trps').removeClass('success').addClass('error').text('请输入验证码');
	    }


	});

	$(document).on('click', '.get_pwd-3', function(event) {
	    event.preventDefault();
	    
	    var pwd1  = $('#password_1').val().replace(/\s+/g,""),
	    pwd2      = $('#password_2').val().replace(/\s+/g,""),
	    err       = 0;

	    if( pwd1 && pwd2 ){

	        if( pwd1 === pwd2 ){
	            $('.password_1,.password_2').removeClass('error').addClass('success');
	            $('.password_1 .lp-trps i,.password_2 .lp-trps i').removeClass().addClass('iconfont icon-duihao');
	            $('.password_1 .lp-trps span,.password_2 .lp-trps span').text('输入正确');
	        }else{
	            $('.password_1,.password_2').removeClass('success').addClass('error');
	            $('.password_1 .lp-trps i,.password_2 .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	            $('.password_1 .lp-trps span,.password_2 .lp-trps span').text('密码不一致');
	            err = 1;
	        }

	    }else{
	        if( !pwd1 ){
	            $('.password_1').removeClass('success').addClass('error');
	            $('.password_1 .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	            $('.password_1 .lp-trps span').text('请输入密码');
	        }
	        if( !pwd2 ){
	            $('.password_2').removeClass('success').addClass('error');
	            $('.password_2 .lp-trps i').removeClass().addClass('iconfont icon-guanbi1');
	            $('.password_2 .lp-trps span').text('请输入密码');
	        }
	        err = 1;
	    }

	    if( err != 1 ){

	        $.ajax({
	            url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: {action: 'get_email_pass' , pwd:pwd2 },
	        })
	        .done(function( data ) {
	            if( data.state == 200 ){
	                $('.s-4').addClass('current');
	                $('.login-trps').removeClass('error').addClass('success').text(data.tips); 
	                $('.w-welpan').html(data.html);
	                setTimeout(function(){window.location.href=data.url;},3000);
	            }else{
	                $('.login-trps').removeClass('success').addClass('error').text(data.tips); 
	            }
	        })
	        .fail(function() {
	            alert('网络错误，请稍候再试！');
	        });
	        

	    }

	});

	$(document).on('click', '#btnGetCode', function(event) {
	    event.preventDefault();
	    
	    $.ajax({
	            url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: {action: 'again_send_vcode' },
	        })
	        .done(function( data ) {
	            if( data.state == 200 ){
	                $('.login-trps').removeClass('error').addClass('success').text(data.tips); 
	                showTime();
	            }else{
	                $('.login-trps').removeClass('success').addClass('error').text(data.tips); 
	            }
	        })
	        .fail(function() {
	            alert('网络错误，请稍候再试！');
	        });

	});

	//海报
	$(document).on('click touchstart', '.btn-bigger-cover', function (event) {
	    event.preventDefault();
	    var poster_img = $('.poster-image img'),
	        btn_poster_img = $('#bigger-cover');
	    if (poster_img.hasClass('load-poster-img')) {
	        $.ajax({
				url: site_url.admin_url,
	            type: 'POST',
	            dataType: 'json',
	            data: btn_poster_img.data(),
	        }).done(function (data) {
	            if (data.state == 200) {
	                poster_img.attr('src', data.src);
	                $('.poster-download-img').attr('href', data.src);
	            } else {
	                alert( data.tips );
	            }
	        }).fail(function () {
	            alert('Error：网络错误，请稍后再试！');
	        })
	    }
	    $('.poster-share').css({'opacity':'1','visibility':'inherit'});   
	
	});
	//关闭窗口
	$(document).on('click touchstart','.poster-close',function() {
	    $('.poster-share').css({'opacity':'0','visibility':'hidden'}); 
	});
	//海报结束

});