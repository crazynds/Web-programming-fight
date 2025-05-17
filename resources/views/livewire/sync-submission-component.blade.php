<div wire:poll.2000ms="refresh">
</div>


@script
    <script type='module'>
        $wire.on('updateSubmissionEvent', (data) => {
            data.forEach(updateSubmission)
        });
    </script>
@endscript
