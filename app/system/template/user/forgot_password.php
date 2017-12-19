<?php
use system\be;
?>
<!--{head}-->
<link type="text/css" rel="stylesheet" href="<?php echo URL_ROOT; ?>/app/system/template/user/css/forgot_password.css">
<script type="text/javascript" language="javascript" src="<?php echo URL_ROOT; ?>/app/system/template/user/js/forgot_password.js"></script>
<!--{/head}-->

<!--{middle}-->
<!--{center}-->
<div class="theme-box-container">
	<div class="theme-box">
		<div class="theme-box-title"><?php echo $this->title; ?></div>
		<div class="theme-box-body">
		
			<form id="form-forgot_password">

				<div class="row">
					<div class="col-8">
						<div class="key">用户名：</div>
					</div>
					<div class="col-12">
						<div class="val">
							<input type="text" name="username" class="input" placeholder="用户名" style="width:200px;" />
						</div>
					</div>
				</div>
				
				<div class="row" style="margin-top:40px;">
					<div class="col-8"></div>
					<div class="col-12">
						<input type="submit" class="btn btn-primary btn-submit" value="找回密码" />
					</div>
				</div>

			</form>

		</div>
	</div>
</div>
<!--{/center}-->
<!--{/middle}-->