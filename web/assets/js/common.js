$( document ).ready(function() {	

	var editDefaultAction = $('#edit-form').attr('action');

	//Отправить запрос на получение данных
	$.ajax({
		url: 'api/locations/',
		type: 'GET',
		error: function(result) {
			if(result.responseJSON){
				text = JSON.stringify(result.responseJSON, null, '\t');
			}else{
				text = result.responseText;
			}
			
			$('#responce-code').text(result.status);
			$('#responce-text').val(text);
		},
		success: function(response, status, xhr) {
			
			if(xhr.responseJSON){
				text = JSON.stringify(xhr.responseJSON, null, '\t');
			}else{
				text = xhr.responseText;
			}
			
			$('#responce-code').text(xhr.status);
			$('#responce-text').val(text);
			
			//Если произошла ошибка
			if(response.status == 'error') return;
			
			//Добавить данные в таблицу
			$.each(response.data, function( i, item ){
				var row = $('<tr class="data-row">')
				
				row.append($('<td class="id">' + item.id + '</td>'));
				row.append($('<td class="name">' + item.name + '</td>'));
				row.append($('<td class="coords"><span class="lat">' + item.lat + '</span><br /><span class="lon">' + item.lon + '</span></td>'));
				row.append($('<td class="temp">' + item.temperature + '</td>'));
				
				row.append($('<td class="source" id="' + item.source_id + '">' + item.source_name + '</td>'));
				row.append($('<td class="pop">' + item.population + '</td>'));
				row.append($('<td class="controls"><button type="button" class="btn btn-primary btn-sm btn-edit" type="Редактировать"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></button><button type="button" class="btn btn-primary btn-sm btn-delete" type="Удалить"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button></td>'));
				
				$("#data-table").append(row);
			});
			
			$('.btn-edit').click(function(){
				//Получить содержимое полей строки таблицы
				var id = $(this).parents('.data-row').find('td.id').text();
				
				//Добавить ID к action формы
				$('#edit-form').attr('action', editDefaultAction + id);
				
				//Отправить запрос на получение данных объекта
				$.ajax({
					url: 'api/locations/' + id,
					type: 'GET',
					data: {
						'all_fields': 1
					},
					error: function(result) {
						if(result.responseJSON){
							text = JSON.stringify(result.responseJSON, null, '\t');
						}else{
							text = result.responseText;
						}
						
						$('#responce-code').text(result.status);
						$('#responce-text').val(text);
					},
					success: function(response, status, xhr) {
						
						if(xhr.responseJSON){
							text = JSON.stringify(xhr.responseJSON, null, '\t');
						}else{
							text = xhr.responseText;
						}
						
						$('#responce-code').text(xhr.status);
						$('#responce-text').val(text);
						
						//Заполнение полей формы
						$('#edit-form input#edit-name').val(response.data.name);
						$('#edit-form input#edit-lat').val(response.data.lat);
						$('#edit-form input#edit-lon').val(response.data.lon);
						$('#edit-form input#edit-temp').val(response.data.temperature);
						$('#edit-form input#edit-pop').val(response.data.population);
						
						//Для каждого источника создать элемент списка
						$.each(response.sources, function( i, item ){
							var option = $('<option value="' + item.id + '">' + item.name  + '</option>');
							if(response.data.source_id == item.id){
								option.attr('selected', 'selected');
							}
							$('#edit-form #edit-source').append(option);
						});
						
						$.each(response.names_i18n, function( i, item ){
							$('#edit-form #lang_' + this.lang_code).val(this.name);
						});
					}
				});
				
				//Скроллить страницу к форме редактирования
				$('html, body').animate({
					scrollTop: $("#edit-form").offset().top
				}, 300);
			});
			
			$('.btn-delete').click(function(){
				//Получить id строки таблицы
				var row = $(this).parents('.data-row');
				var id = $(row).find('td.id').text();
				//Отправить запрос на удаление				
				$.ajax({
					url: 'api/locations/' + id,
					type: 'DELETE',
					complete: function(){
						//Скроллить страницу к ответу сервера
						$('html, body').animate({
							scrollTop: $("#responce").offset().top
						}, 300);
					},
					error: function(result) {
						if(result.responseJSON){
							text = JSON.stringify(result.responseJSON, null, '\t');
						}else{
							text = result.responseText;
						}
						
						$('#responce-code').text(result.status);
						$('#responce-text').val(text);
					},
					success: function(response, status, xhr) {
						
						if(xhr.responseJSON){
							text = JSON.stringify(xhr.responseJSON, null, '\t');
						}else{
							text = xhr.responseText;
						}
						
						$('#responce-code').text(xhr.status);
						$('#responce-text').val(text);				
						
						//Если удаление прошло успешно - удалить строку из таблицы
						$(row).remove();
					}
				});
			});
			
		}
	});
	
	//Cброс формы редактирования
	$('#edit-form .btn-reset').click(function(){
		$('#edit-form').attr('action', editDefaultAction);
		$('#edit-form #edit-source option').each(function(i, item){
			if($(this).attr('value') > 0){
				$(this).remove();
			}else{
				$(this).attr('selected', 'selected');
			}
		});
	});
	
	//AJAX отправка форм для демонстрации ответа сервера
	$('#add-form, #edit-form').submit(function(e){
		
		 e.preventDefault();
		
		var form = $(e.target);
		
		$.ajax({
			url: form.attr('action'),
			type: 'POST',
			data: form.serialize(),
			complete: function(){
				//Скроллить страницу к ответу сервера
				$('html, body').animate({
					scrollTop: $("#responce").offset().top
				}, 300);
			},
			error: function(result) {
				if(result.responseJSON){
					text = JSON.stringify(result.responseJSON, null, '\t');
				}else{
					text = result.responseText;
				}
				
				$('#responce-code').text(result.status);
				$('#responce-text').val(text);
			},
			success: function(response, status, xhr) {
				
				if(xhr.responseJSON){
					text = JSON.stringify(xhr.responseJSON, null, '\t');
				}else{
					text = xhr.responseText;
				}
				
				$('#responce-code').text(xhr.status);
				$('#responce-text').val(text);
			}
		});		
	});
	
});