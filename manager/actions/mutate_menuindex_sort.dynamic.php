<?php
if(IN_MANAGER_MODE != "true") {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('edit_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : NULL;
$reset = isset($_POST['reset']) && $_POST['reset'] == 'true' ? 1 : 0;
$items = isset($_POST['list']) ? $_POST['list'] : '';
$ressourcelist = '';
$updateMsg = '';

// check permissions on the document
include_once MODX_MANAGER_PATH . "processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if(!$udperms->checkPermissions()) {
	$modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

if(isset($_POST['listSubmitted'])) {
	$updateMsg .= '<div class="text-success" id="updated">' . $_lang['sort_updated'] . '</div>';
	if(strlen($items) > 0) {
		$items = explode(';', $items);
		foreach($items as $key => $value) {
			$docid = ltrim($value, 'item_');
			$key = $reset ? 0 : $key;
			if(is_numeric($docid)) {
				$modx->db->update(array('menuindex' => $key), $modx->getFullTableName('site_content'), "id='{$docid}'");
			}
		}
	}
}

$disabled = 'true';
$pagetitle = '';
$ressourcelist = '';
if($id !== NULL) {
	$rs = $modx->db->select('pagetitle', $modx->getFullTableName('site_content'), "id='{$id}'");
	$pagetitle = $modx->db->getValue($rs);

	$rs = $modx->db->select('id, pagetitle, parent, menuindex, published, hidemenu, deleted, isfolder', $modx->getFullTableName('site_content'), "parent='{$id}'", 'menuindex ASC');
	if($modx->db->getRecordCount($rs)) {
		$ressourcelist .= '<div class="clearfix"><ul id="sortlist" class="sortableList">';
		while($row = $modx->db->getRow($rs)) {
			$classes = '';
			$classes .= ($row['hidemenu']) ? ' notInMenuNode ' : ' inMenuNode';
			$classes .= ($row['published']) ? ' publishedNode ' : ' unpublishedNode ';
			$classes = ($row['deleted']) ? ' deletedNode ' : $classes;
			$icon = $row['isfolder'] ? '<i class="' . $_style['files_folder'] . '"></i> ' : ' <i class="' . $_style['files_page_html'] . '"></i> ';
			$ressourcelist .= '<li id="item_' . $row['id'] . '" class="' . $classes . '">' . $icon . $row['pagetitle'] . ' <small>(' . $row['id'] . ')</small></li>';
		}
		$ressourcelist .= '</ul></div>';
	} else {
		$updateMsg = '<p class="text-danger">' . $_lang['sort_nochildren'] . '</p>';
	}
}

$pagetitle = $id == 0 ? $site_name : $pagetitle;
?>

<script type="text/javascript">

	parent.tree.updateTree();

	var actions = {
		save: function() {
			var el = document.getElementById('updated');
			if(el) el.style.display = 'none';
			el = document.getElementById('updating');
			if(el) el.style.display = 'block';
			setTimeout("document.sortableListForm.submit()", 1000);
		},
		cancel: function() {
			document.location.href = 'index.php?a=2';
		}
	};

	function renderList() {
		var list = '';
		var els = document.querySelectorAll('.sortableList > li');
		for(var i = 0; i < els.length; i++) {
			list += els[i].id + ';';
		}
		document.getElementById('list').value = list
	}

	var sortdir = 'asc';

	function sort() {
		var els = document.querySelectorAll('.sortableList > li');
		var keyA, keyB;
		if(sortdir === 'asc') {
			els = [].slice.call(els).sort(function(a, b) {
				keyA = a.innerText.toLowerCase();
				keyB = b.innerText.toLowerCase();
				return keyA.localeCompare(keyB);
			});
			sortdir = 'desc'
		} else {
			els = [].slice.call(els).sort(function(b, a) {
				keyA = a.innerText.toLowerCase();
				keyB = b.innerText.toLowerCase();
				return keyA.localeCompare(keyB);
			});
			sortdir = 'asc'
		}
		var ul = document.getElementById('sortlist');
		var list = '';
		for(var i = 0; i < els.length; i++) {
			ul.appendChild(els[i]);
			list += els[i].id + ';';
		}
		document.getElementById('list').value = list
	}

	function resetSortOrder() {
		if(confirm("<?= $_lang["confirm_reset_sort_order"] ?>") === true) {
			documentDirty = false;
			var input = document.createElement("input");
			input.type = "hidden";
			input.name = "reset";
			input.value = "true";
			document.sortableListForm.appendChild(input);
			actions.save();
		}
	}

</script>

<h1>
	<i class="fa fa-sort-numeric-asc"></i><?= $_lang["sort_menuindex"] ?>
</h1>

<?= $_style['actionbuttons']['dynamic']['save'] ?>

<div class="tab-page">
	<div class="container container-body">
		<b><?= $pagetitle ?> (<?= $id ?>)</b>
		<?php
		if($ressourcelist) {
			?>
			<p><?= $_lang["sort_elements_msg"] ?></p>
			<p>
				<a class="btn btn-secondary" href="javascript:;" onclick="sort();return false;"><i class="<?= $_style['actions_sort'] ?>"></i> <?= $_lang['sort_alphabetically'] ?></a>
				<a class="btn btn-secondary" href="javascript:;" onclick="resetSortOrder();return false;"><i class="<?= $_style['actions_refresh'] ?>"></i> <?= $_lang['reset_sort_order'] ?></a>
			</p>
			<?= $updateMsg ?>
			<span class="text-danger" style="display:none;" id="updating"><?= $_lang['sort_updating'] ?></span>
			<?= $ressourcelist ?>
			<?
		} else {
			echo $updateMsg;
		}
		?>
	</div>
</div>

<form action="" method="post" name="sortableListForm">
	<input type="hidden" name="listSubmitted" value="true" />
	<input type="hidden" id="list" name="list" value="" />
</form>

<script type="text/javascript">

	[].slice.call(document.querySelectorAll('.sortableList > li')).forEach(function(a) {
		a.onmousedown = function(e) {
			var b = e.pageY, c, f = parseFloat(getComputedStyle(a).marginTop) + parseFloat(getComputedStyle(a).marginBottom);
			a.classList.add('ghost', 'text-danger');
			document.onselectstart = function(e) {
				e.preventDefault()
			};
			document.onmousemove = function(e) {
				c = (e.pageY - b);
				if(c >= a.offsetHeight && a.nextSibling) {
					b += a.offsetHeight + f;
					a.parentNode.insertBefore(a, a.nextSibling.nextSibling);
					c = 0
				} else if(c <= -a.offsetHeight && a.previousSibling) {
					b -= a.offsetHeight + f;
					a.parentNode.insertBefore(a, a.previousSibling);
					c = 0
				} else if(!a.previousSibling && c < 0 || !a.nextSibling && c > 0) {
					c = 0;
				}
				a.style.webkitTransform = 'translateY(' + c + 'px)';
				a.style.transform = 'translateY(' + c + 'px)';
			};
			document.onmouseup = function() {
				a.style.webkitTransform = '';
				a.style.transform = '';
				a.classList.remove('ghost', 'text-danger');
				document.onmousemove = null;
				document.onselectstart = null;
				renderList();
			}
		}
	});

</script>
