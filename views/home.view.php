<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <title>icloudems</title>
</head>

<body>
  <div class="flex justify-center items-center min-h-screen w-full bg-gray-50 p-4">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden" style="width: 100%; max-width: 500px;">
      <!-- Card Header -->
      <div class="bg-blue-600 text-white py-4 px-6">
        <h2 class="text-xl font-bold text-center"><i class="fas fa-file-csv mr-2"></i>CSV File Upload</h2>
      </div>

      <!-- Card Body -->
      <div class="p-6">
        <form action="/insert.php" method="POST" enctype="multipart/form-data">
          <!-- File Input with custom styling -->
          <div class="mb-6">
            <label class="block">
              <span class="sr-only">Choose CSV file</span>
              <input type="file" name="csv_file"
                class="block w-full text-sm text-gray-500
                          file:mr-4 file:py-3 file:px-4
                          file:rounded-lg file:border-0
                          file:text-sm file:font-semibold
                          file:bg-blue-50 file:text-blue-700
                          hover:file:bg-blue-100
                          focus:outline-none focus:ring-2 focus:ring-blue-500">
            </label>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
            Process CSV File
          </button>
        </form>
      </div>

      <!-- Card Footer -->
      <div class="bg-gray-100 py-3 px-6 text-center">
        <p class="text-xs text-gray-500">Supported formats: .csv (comma separated values)</p>
      </div>
    </div>
  </div>
</body>

</html>