<?php
$url_prefix = URL.$filename.'/';

$feedback = Session::get('new_message_response');
Session::set('new_message_response', null);

$post = Session::get('new_message_post');
Session::set('new_message_post', null);

?>
<div class="content rows-20">
	<form method="post" class="rows-20" action="<?php echo URL; ?>account/send_message/">
        <div class="row header">
            <h3>New Message</h3>
        </div>
        <hr />
        <div class="row">
            <div class="cols-15">
                <div class="col-8">
                    <label class="label">Recipient User's Alias</label>
                    <label class="text<?php echo isset($feedback['recipient_alias']) ? ' invalid' : false ?>">
                        <input type="text" name="recipient_alias" required class="prepend" <?php echo isset($post['recipient_alias']) ? strip_tags($post['recipient_alias']) : ($this->recipient ? 'value="'.strip_tags($this->recipient).'"' : false); ?>/>
                        <i class="fa-user"></i>
                        <?php if ( isset($feedback['recipient_alias']) ){ ?>
                        <p class="note"><?php echo $feedback['recipient_alias'] ?></p>
                        <?php } ?>
                    </label>
                </div>
                <div class="col-4">
                    <label class="label">Auto-delete</label>
                    <label class="select<?php echo isset($feedback['auto_delete']) ? ' invalid' : false ?>">
                        <select name="auto_delete">
                            <option value="0"<?php echo $post['auto_delete']==0 ? ' selected' : false ?>>Never</option>
                            <option value="30"<?php echo $post['auto_delete']==30 ? ' selected' : false ?>>After 1 month</option>
                            <option value="7" <?php echo !isset($post['auto_delete']) || $post['auto_delete']==7 ? ' selected' : false ?>>After 1 week</option>
                            <option value="3"<?php echo $post['auto_delete']==3 ? ' selected' : false ?>>After 3 days</option>
                            <option value="1"<?php echo $post['auto_delete']==1 ? ' selected' : false ?>>After 1 day</option>
                        </select>
                        <i class="fa-caret-down"></i>
                        <?php if ( isset($feedback['auto_delete']) ){ ?>
                        <p class="note"><?php echo $feedback['auto_delete'] ?></p>
                        <?php } ?>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <label class="label">Message Content</label>
            <label class="textarea<?php echo isset($feedback['content']) ? ' invalid' : false ?>">
                <textarea rows="22" name="content"><?php echo isset($post['content']) ? strip_tags($post['content']) : false; ?></textarea>
                <?php if ( isset($feedback['content']) ){ ?>
                <p class="note"><?php echo $feedback['content'] ?></p>
                <?php } ?>
            </label>
        </div>
        <div class="row cols-15">
            <div class="col-6"></div>
            <div class="col-3">
                <input class="btn color wide" type="submit" value="Send">
            </div>
            <div class="col-3">
                <a class="btn color red wide" href="<?php echo URL?>account/messages/"><i class="fa-trash-o"></i>Discard</a>
            </div>
        </div>
    </form>
</div>