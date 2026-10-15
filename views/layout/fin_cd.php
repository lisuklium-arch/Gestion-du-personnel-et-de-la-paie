      </main>
    </div>
  </div>

  <?php include __DIR__ . '/navbar_mobile_cd.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php foreach ($scripts ?? [] as $script): ?>
  <script src="/public/js/<?= $script ?>"></script>
  <?php endforeach; ?>
</body>
</html>
