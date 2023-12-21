<?php 

require('messages/header.php'); 

function renderChatStatuses($recursiveChatStatuses, $activeStatusID, $level = 0){
	foreach($recursiveChatStatuses as $recursiveChatStatus){
		$option =
			'<option value="' .
			$recursiveChatStatus['ID'] .
			'"' .
			(
				$recursiveChatStatus['ID'] == $activeStatusID
					? ' selected'
					: FALSE
			) .
			'>' . 
			str_repeat('&nbsp;', $level) .
			$recursiveChatStatus['Title'] .
			'</option>';
		echo $option;
		
		if( !empty($recursiveChatStatus['Children']) )
			renderChatStatuses($recursiveChatStatus['Children'], $activeStatusID, $level + 1);
	}
}

$URLPrefix = URL . 'account/support_overview/' . $this->filterMode . '/';

?>
		<div class="content">
			<input id="new_ticket-model" hidden type="checkbox">
			<div class="modal wide">
				<label for="new_ticket-model"></label>
				<div class="rows-10">
					<label for="new_ticket-model" class="close">&times;</label>
					<form method="post" class="rows-20" action="<?php echo URL . 'account/create_new_ticket/'; ?>">
						<fieldset>
							<label class="label">User's Alias</label>
							<label class="text">
								<input name="subject_alias" required class="prepend" type="text">
								<i class="<?php echo Icon::getClass('USER'); ?>"></i>
							</label>
						</fieldset>
						<fieldset class="rows-5">
							<label class="row text inline">
								<input name="note" placeholder="Subject, Comments, Notes (for internal use only)" class="prepend" type="text">
								<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
								<b></b>
							</label>
							<div class="row cols-5">
								<div class="col-8">
									<label class="textarea chat-textarea">
										<textarea name="message" required placeholder="Write message…"></textarea>
									</label>
								</div>
								<div class="col-4 rows-5">
									<label class="row select prepend">
										<?php if ($this->modUsernames){ ?>
										<select name="sender">
											<?php foreach($this->modUsernames as $modUsername){ ?>
											<option<?= $modUsername == $this->UserAlias ? ' selected' : false ?>><?= $modUsername; ?></option>
											<?php } ?>
										</select>
										<?php } else { ?>
										<select disabled>
											<option><?php echo $this->UserAlias; ?></option>
										</select>
										<?php } ?>
										<i class="<?= Icon::getClass('USER'); ?>"></i>
									</label>
									<button type="submit" class="row btn wide">Send</button>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
			<div class="chat-box not-fixed">
				<div>
					<div class="chat-info">
						<div class="panel">
							<div class="right">
								<label class="label">Filter</label>
								<div class="big-dropdown">
									<span><?php echo $this->filterModeOptions[$this->filterMode]; ?></span>
									<a class="toggle"></a>
									<ul class="dropdown">
										<?php foreach($this->filterModeOptions as $filterModeOptionAlias => $filterModeOption){
										if($filterModeOptionAlias == $this->filterMode)
											continue;
										?>
										<li>
											<a class="dropdown-link" href="<?php echo URL . 'account/support_overview/' . $filterModeOptionAlias . '/' ?>"><?php echo $filterModeOption; ?></a>
										</li>
										<?php } ?>
									</ul>
								</div>
								<?php if($this->supportChats){ ?>
								<button class="btn xs" type="submit" form="chats-form" name="redirect" value="<?php echo URL . 'account/support_overview/' . $this->filterMode . '/'; ?>">
									<i class="<?php echo Icon::getClass('SAVE'); ?>"></i>
									<div class="hint below">
										<span>Save changes</span>
									</div>
								</button>
								<?php } ?>
							</div>
							<div class="left">
								<label for="new_ticket-model" class="btn red">
									<i class="<?php echo Icon::getClass('PLUS'); ?>"></i>
									Create Ticket
								</label>
								
							</div>
						</div>
					</div>
					<?php if($this->supportChats){ ?>
					<form id="chats-form" method="post" action="<?php echo URL . 'account/update_chats/'; ?>">
						<?php foreach($this->supportChats as $supportChat){ ?>
						<input name="assigned-<?php echo $supportChat['ID']; ?>" type="checkbox" hidden <?php echo 'id="assigned-' . $supportChat['ID'] . '"' . ($supportChat['Assigned'] ? ' checked' : FALSE); ?>>
						<?php } ?>
						<table id="support-table" class="cool-table unbanded">
							<thead>
								<tr>
									<th>
										<?php if($this->sortMode == 'priority_desc'){ ?>
										<a href="<?php echo $URLPrefix . 'priority_desc/'; ?>">Status</a>
										<?php } else echo 'Status'; ?>
									</th>
									<th>Assigned</th>
									<th><a href="<?php echo $URLPrefix . ($this->sortMode == 'user_asc' ? 'user_desc' : 'user_asc') . '/'; ?>">User<?php 
									
										switch($this->sortMode){
											case 'user_asc':
												echo ' <i class="' . Icon::getClass('CARET_UP') . '"></i>';
											break;
											case 'user_desc':
												echo ' <i class="' . Icon::getClass('CARET_DOWN') . '"></i>';
											break;
										}
						
									?></a></th>
									<th>&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($this->supportChats as $supportChat){ ?>
								<tr id="ticket-<?php echo $supportChat['ID'] ?>" data-ticket-id="<?php echo $supportChat['ID'] ?>">
									<td>
										<input name="chat_ids[]" value="<?php echo $supportChat['ID'] ?>" type="hidden">
										<label class="select prepend">
											<select name="chat_status_id-<?php echo $supportChat['ID']; ?>">
												<?php renderChatStatuses($this->recursiveChatStatuses, $supportChat['StatusID']); ?>
											</select>
											<i class="<?php echo $supportChat['StatusIcon'] . ($supportChat['StatusColor'] ? ' color-' . $supportChat['StatusColor'] : FALSE) ?>"></i>
										</label>
									</td>
									<td>
										<label class="checkbox" for="assigned-<?php echo $supportChat['ID']; ?>">
											<i></i>
										</label>
									</td>
									<td>
										<a href="<?php echo URL . 'u/' . $supportChat['SubjectAlias'] . '/'; ?>"><?php echo $supportChat['SubjectAlias']; ?></a>
									</td>
									<td>
										<button class="btn xs" type="submit" name="redirect" value="<?php echo URL . 'account/support/' . $supportChat['SubjectAlias'] . '/' ?>">
											<i class="<?php echo Icon::getClass('FORWARD'); ?>"></i>
											<div class="hint above">
												<span>View</span>
											</div>
										</button>
									</td>
								</tr>
								<tr data-ticket-id="<?php echo $supportChat['ID'] ?>"<?php echo !$supportChat['LatestNote'] ? ' class="visible-assigned"' : FALSE; ?>>
									<td colspan="4">
										<fieldset class="rows-5">
											<label class="row text inline">
												<input name="chat_note-<?php echo $supportChat['ID']; ?>" placeholder="Subject, Comments, Notes (for internal use only)" class="prepend"<?php echo $supportChat['LatestNote'] ? ' value="' . $supportChat['LatestNote'] . '"' : FALSE; ?> type="text">
												<i class="<?php echo Icon::getClass('BROWSER'); ?>"></i>
												<b></b>
											</label>
											<div class="row cols-5 visible-assigned">
												<div class="col-9">
													<label class="textarea chat-textarea">
														<textarea name="chat_message-<?php echo $supportChat['ID']; ?>" placeholder="Write message…"></textarea>
													</label>
												</div>
												<div class="col-3 rows-5">
													<label class="row select prepend">
														<?php if ($this->modUsernames){ ?>
														<select name="sender">
															<?php foreach($this->modUsernames as $modUsername){ ?>
															<option<?= $modUsername == $this->UserAlias ? ' selected' : false ?>><?= $modUsername; ?></option>
															<?php } ?>
														</select>
														<?php } else { ?>
														<select disabled>
															<option><?php echo $this->UserAlias; ?></option>
														</select>
														<?php } ?>
														<i class="<?= Icon::getClass('USER'); ?>"></i>
													</label>
													<button class="row btn wide" type="submit" name="redirect-<?php echo $supportChat['ID']; ?>" value="<?php echo URL . 'account/support_overview/' . $this->filterMode . '/#ticket-' . $supportChat['ID']; ?>">Send</button>
												</div>
											</div>
										</fieldset>
										<?php if($supportChat['latestMessages']){ ?>
										<div class="visible-assigned">
											<ol class="list-discussion">
												<?php if( $supportChat['messageCount'] > SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_QUANTITY ){ ?>
												<li class="remark">
													<span>
														<strong><?php $earlierMessageCount = NXS::formatNumber($supportChat['messageCount'] - SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_QUANTITY); echo $earlierMessageCount; ?></strong> message<?php echo $earlierMessageCount == 1 ? FALSE : 's'; ?> before this&hellip;
													</span>
												</li>
												<?php }
												foreach($supportChat['latestMessages'] as $i => $latestMessage){ 
													switch($latestMessage['type']){
														case CHAT_MESSAGE_ENTRY_TYPE_MESSAGE:
															
														?>
												<li class="<?php
												
												$class =
													'sender-' .
													$latestMessage['SenderAlias'] .
													(
														$latestMessage['Color']
															? ' ' . $latestMessage['Color']
															: FALSE
													) .
													(
														$latestMessage['Unread'] && $latestMessage['SenderAlias'] == $this->UserAlias
															? ' fade-in'
															: FALSE
													); 
												
												echo $class;
												
												?>">
												<?php if($latestMessage['Unread']){ ?>
												<input type="hidden" name="chat_messages-<?php echo $supportChat['ID']; ?>[]" value="<?php echo $latestMessage['ID']; ?>">
												<input type="radio" name="mark_read-<?php echo $latestMessage['ID']; ?>" value="<?php echo $latestMessage['ID'] ?>" id="mark_read-<?php echo $latestMessage['ID'] ?>" class="toggle-read"<?php echo $latestMessage['SenderAlias'] == $this->UserAlias ? ' checked' : FALSE; ?> hidden>
												<?php } ?>
													<div class="meta">
														<a class="username" href="<?php echo URL . 'u/' . $latestMessage['SenderAlias'] . '/'; ?>"><?php echo $latestMessage['SenderAlias']; ?></a>
														<time><?php echo $latestMessage['date']; ?><span><?php echo $latestMessage['time']; ?></span></time>
														<b></b>
													</div>
													<div class="comment">
														<div>
															<<?php echo $latestMessage['Unread'] ? 'label for="mark_read-' . $latestMessage['ID'] . '"' : 'div'; ?> class="messages formatted">
																<?php echo $latestMessage['HTML']; ?>
															</<?php echo $latestMessage['Unread'] ? 'label' : 'div'; ?>>
														</div>
													</div>
												</li>
												<?php break;
												case CHAT_MESSAGE_ENTRY_TYPE_EVENT: 
												if(
													!isset($supportChat['latestMessages'][$i + 1]) ||
													$supportChat['latestMessages'][$i + 1]['type'] !== CHAT_MESSAGE_ENTRY_TYPE_EVENT	
												){ ?>
												<li class="remark">
													<span>
														<?php echo $latestMessage['text']; ?>
													</span>
												</li>
												<?php }
												break;
												case CHAT_MESSAGE_ENTRY_TYPE_NOTE: ?>
												<li class="remark">
													<span>
														<strong><?php echo $latestMessage['AuthorAlias']; ?></strong> added a remark:<br><?php echo $latestMessage['Note']; ?>
													</span>
												</li>
												<?php break;
												}
												} ?>
											</ol>
										</div>
										<?php } ?>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</form>
					<div class="panel">
						<?php
						$this->renderPaginationPanel(
							$this->pageNumber,
							$this->numberOfPages,
							$URLPrefix . $this->sortMode . '/'
						);
						?>
					</div>
					<?php } else { ?>
					<h3 class="centered">no support tickets found</h3>
					<?php } ?>
				</div>
			</div>
		</div>
<?php require('messages/footer.php'); ?>
