export const compressImage = async (file, options = {}) => {
  const {
    maxDimension = 2000,
    minDimension = 500,
    maxBytes = 150 * 1024,
    startQuality = 0.82,
    minQuality = 0.25,
    step = 0.08
  } = options

  const original = await loadImage(file)
  const format = supportsWebP ? 'image/webp' : 'image/jpeg'

  const fits = (blob) => blob.size <= maxBytes

  let scale = Math.min(1, maxDimension / Math.max(original.width, original.height))
  let quality = startQuality
  let best = null

  while (true) {
    const width = Math.max(1, Math.round(original.width * scale))
    const height = Math.max(1, Math.round(original.height * scale))

    if (scale > 0 && width * height > 1) {
      const blob = await render(original, { width, height, format, quality })
      if (!best || blob.size < best.size) best = blob

      if (fits(blob)) {
        return best
      }

      if (quality > minQuality) {
        quality = Math.max(minQuality, quality - step)
        continue
      }
    }

    if (Math.max(width, height) <= minDimension) {
      return best
    }

    scale *= 0.75
    quality = startQuality
  }
}

const loadImage = (file) =>
  new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error('Unable to read the selected image.'))
    }
    img.src = url
  })

const render = (image, { width, height, format, quality }) =>
  new Promise((resolve, reject) => {
    const canvas = document.createElement('canvas')
    canvas.width = width
    canvas.height = height
    const ctx = canvas.getContext('2d')
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, width, height)
    ctx.imageSmoothingEnabled = true
    ctx.imageSmoothingQuality = 'high'
    ctx.drawImage(image, 0, 0, width, height)
    canvas.toBlob(
      (blob) => {
        if (blob) {
          resolve(blob)
        } else {
          reject(new Error('Image compression failed.'))
        }
      },
      format,
      quality
    )
  })

const supportsWebP =
  typeof document === 'undefined' ? false : document.createElement('canvas').toDataURL('image/webp').startsWith('data:image/webp')