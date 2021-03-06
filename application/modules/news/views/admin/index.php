<? $this->load->helper('date');?>
      
<?= form_open('admin/news/delete');?>

<table border="0" class="listTable">
    
  <thead>
	<tr>
		<th class="first"><div></div></th>
		<th><a href="#">Post</a></th>
		<th><a href="#">Category</a></th>
		<th><a href="#">Date</a></th>
		<th><a href="#">Status</a></th>
		<th class="last"><span>Actions</span></th>
	</tr>
  </thead>
  <tfoot>
  	<tr>
  		<td colspan="6">
  			<div class="inner"></div>
  		</td>
  	</tr>
  </tfoot>

  <tbody>
	<? if (!empty($news)): ?>
  		<? foreach ($news as $article): ?>
		<tr>
			<td><input type="checkbox" name="delete[<?=$article->id;?>]" /></td>
            <td><?=$article->title;?></td>
            <td><?=$article->category_title;?></td>
            <td><?=date('M d, Y', $article->created_on);?></td>
            <td><?=ucfirst($article->status);?></td>
            <td>
            	<? if( $article->status == 'draft' ): ?>
            	<?= anchor('admin/news/preview/'. $article->slug, 'Preview', 'rel="modal" target="_blank"') . ' | '; ?>
            	<? else: ?>
            	<?= anchor('news/' .date('Y/m', $article->created_on) .'/'. $article->slug, 'View', 'target="_blank"') . ' | '; ?>
            	<? endif; ?>
			  	<?= anchor('admin/news/edit/' . $article->id, 'Edit') . ' | ' .
			  		anchor('admin/news/delete/' . $article->id, 'Delete', array('class'=>'confirm')); ?>
            </td>
        </tr>
		<? endforeach; ?>
	<? else: ?>
  		<tr><td colspan="6">There are no articles.</td></tr>
	<? endif; ?>
	</tbody>
	
</table>

<? $this->load->view('admin/layout_fragments/table_buttons', array('buttons' => array('delete') )); ?>
