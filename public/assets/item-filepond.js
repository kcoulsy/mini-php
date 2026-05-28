(function () {
  var input = document.getElementById('item-attachments');

  if (!input || typeof FilePond === 'undefined') {
    return;
  }

  var existingRaw = input.getAttribute('data-existing-files') || '[]';
  var existingFiles = [];

  try {
    existingFiles = JSON.parse(existingRaw);
  } catch (e) {
    existingFiles = [];
  }

  var removedContainer = document.getElementById('removed-attachment-ids');

  FilePond.create(input, {
    allowMultiple: true,
    storeAsFile: true,
    credits: false,
    files: existingFiles,
    onremovefile: function (error, file) {
      if (error || !removedContainer) {
        return;
      }

      var id = file.getMetadata('attachmentId');

      if (!id) {
        return;
      }

      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'removed_attachment_ids[]';
      hidden.value = String(id);
      removedContainer.appendChild(hidden);
    },
  });
})();
