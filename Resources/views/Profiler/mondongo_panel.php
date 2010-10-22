<h2>Queries</h2>

<?php if (!$data->getNbQueries()): ?>
    <em>No queries.</em>
<?php else: ?>
    <ul class="alt">
        <?php foreach ($data->getFormattedQueries() as $i => $query): ?>
            <li class="<?php echo $i % 2 ? 'odd' : 'even' ?>">
                <div>
                    <code><pre><?php echo $query ?></pre></code>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
