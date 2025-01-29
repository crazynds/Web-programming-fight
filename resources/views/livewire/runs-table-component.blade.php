<div wire:poll.2000ms.visible="refresh">
</div>


@script
    <script>
        $wire.on('updateSubmissionEvent', (data) => {
            data.forEach(updateSubmission)
        });
    </script>
@endscript
